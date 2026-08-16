<?php

namespace App\Services\Inventory;

use App\Models\InventoryLocation;
use App\Models\InventoryUnit;
use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR labels for asset tags and location barcodes.
 *
 * Unlike the bookstore's QrLabelService, the payload here is the *code itself*
 * (SITS-IT-000871, LOC-MC-0031), not a URL. Asset tags are stuck on physical
 * things that outlive domain names, and the store's own scan box resolves a code
 * to a screen — so encoding a URL would only make the sticker fragile without
 * making it more useful. A phone camera still reads the code as plain text,
 * which is exactly what a counter types into the stocktake screen.
 *
 * Images are generated on demand and never stored: a code is immutable, so the
 * image is a pure function of the model and there is no file to go stale.
 */
class InventoryLabelService
{
    /** The string encoded in the QR — what a scanner will hand back. */
    public function payload(Model $model): string
    {
        return match (true) {
            $model instanceof InventoryUnit => (string) $model->asset_tag,
            $model instanceof InventoryLocation => (string) $model->code,
            default => (string) $model->getKey(),
        };
    }

    /**
     * SVG rather than PNG by default: PNG goes through BaconQrCode's imagick
     * back end, and shared cPanel hosting does not always have the extension.
     * SVG renders identically in the browser and in dompdf with no extension at
     * all.
     */
    public function svg(Model $model, int $size = 220): string
    {
        return (string) QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($this->payload($model));
    }

    public function png(Model $model, int $size = 220): string
    {
        return (string) QrCode::format('png')
            ->size($size)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($this->payload($model));
    }

    public function supportsPng(): bool
    {
        return extension_loaded('imagick');
    }

    /** Data URI for embedding in printable HTML/PDF without a second request. */
    public function dataUri(Model $model, int $size = 220): string
    {
        return $this->supportsPng()
            ? 'data:image/png;base64,'.base64_encode($this->png($model, $size))
            : 'data:image/svg+xml;base64,'.base64_encode($this->svg($model, $size));
    }

    /**
     * One sticker's worth of data. The caption matters more than the code: a
     * store keeper reads the text, and only a scanner reads the square.
     *
     * @return array{qr: string, code: string, name: string, sub: ?string, meta: ?string}
     */
    public function label(Model $model, int $size = 220): array
    {
        return match (true) {
            $model instanceof InventoryUnit => [
                'qr' => $this->dataUri($model, $size),
                'code' => (string) $model->asset_tag,
                'name' => (string) ($model->item?->name_en ?? 'Asset'),
                'sub' => $model->serial_number ? 'S/N '.$model->serial_number : $model->item?->code,
                'meta' => $model->location?->name,
            ],
            $model instanceof InventoryLocation => [
                'qr' => $this->dataUri($model, $size),
                'code' => (string) $model->code,
                'name' => (string) $model->name,
                'sub' => $model->type?->label(),
                'meta' => $model->fullPath(),
            ],
            default => [
                'qr' => $this->dataUri($model, $size),
                'code' => $this->payload($model),
                'name' => class_basename($model).' #'.$model->getKey(),
                'sub' => null,
                'meta' => null,
            ],
        };
    }

    /**
     * A sheet of labels for bulk printing.
     *
     * @param  iterable<Model>  $models
     * @return array<int, array{qr: string, code: string, name: string, sub: ?string, meta: ?string}>
     */
    public function sheet(iterable $models, int $size = 200): array
    {
        $labels = [];

        foreach ($models as $model) {
            $labels[] = $this->label($model, $size);
        }

        return $labels;
    }

    /**
     * Resolve a scanned code back to whatever it is stuck on. Asset tags and
     * location codes can't collide (SITS- vs LOC- prefixes), so one box on the
     * stocktake screen accepts either.
     *
     * @return array{type: string, model: Model}|null
     */
    public function resolve(string $code): ?array
    {
        $code = trim($code);

        if ($unit = InventoryUnit::where('asset_tag', $code)->first()) {
            return ['type' => 'unit', 'model' => $unit];
        }

        if ($location = InventoryLocation::where('code', $code)->first()) {
            return ['type' => 'location', 'model' => $location];
        }

        // Fall back to a serial number: not every asset carries our sticker, but
        // most carry the manufacturer's.
        if ($unit = InventoryUnit::where('serial_number', $code)->first()) {
            return ['type' => 'unit', 'model' => $unit];
        }

        return null;
    }
}
