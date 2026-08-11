<?php

namespace App\Services\Bookstore;

use App\Models\BookDispatch;
use App\Models\BookTitle;
use App\Models\Shelf;
use App\Models\ShelfSection;
use App\Models\StoreRoom;
use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Every QR in the bookstore comes from here.
 *
 * The payload is an absolute URL to the scan resolver, not a bare hash, so any
 * phone camera opens the right screen without our app installed. Images are
 * generated on demand and never stored: the hash is immutable, so the image is
 * a pure function of the model and there is no file to go stale.
 */
class QrLabelService
{
    /** Models that carry a scannable hash, keyed by the token used in the label sheet. */
    public const SCANNABLE = [
        'title'   => BookTitle::class,
        'store'   => StoreRoom::class,
        'shelf'   => Shelf::class,
        'section' => ShelfSection::class,
        'waybill' => BookDispatch::class,
    ];

    /** The URL encoded in the QR. */
    public function payload(Model $model): string
    {
        return route('bookstore.scan.resolve', ['hash' => $model->tracking_hash]);
    }

    /**
     * PNG output goes through BaconQrCode's imagick back end. Shared cPanel
     * hosting does not always have the extension, so callers should prefer
     * {@see self::dataUri()}, which falls back to SVG.
     */
    public function png(Model $model, int $size = 320): string
    {
        return (string) QrCode::format('png')
            ->size($size)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($this->payload($model));
    }

    public function svg(Model $model, int $size = 320): string
    {
        return (string) QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($this->payload($model));
    }

    /** True when the PNG back end can actually run on this server. */
    public function supportsPng(): bool
    {
        return extension_loaded('imagick');
    }

    /**
     * A data: URI for embedding in printable HTML/PDF without a second request.
     *
     * SVG when imagick is missing — it renders identically in the browser and in
     * dompdf, and needs no PHP extension at all.
     */
    public function dataUri(Model $model, int $size = 320): string
    {
        return $this->supportsPng()
            ? 'data:image/png;base64,'.base64_encode($this->png($model, $size))
            : 'data:image/svg+xml;base64,'.base64_encode($this->svg($model, $size));
    }

    /**
     * The human-readable caption printed directly beneath the QR — the whole
     * point of the label, since a store keeper reads the text, not the code.
     *
     * @return array{name: string, sub: string|null}
     */
    public function caption(Model $model): array
    {
        return match (true) {
            $model instanceof BookTitle    => ['name' => $model->title, 'sub' => $model->code],
            $model instanceof ShelfSection => ['name' => $model->code, 'sub' => $model->path],
            $model instanceof Shelf        => ['name' => $model->label ?: $model->code, 'sub' => $model->path],
            $model instanceof StoreRoom    => ['name' => $model->name, 'sub' => $model->code],
            $model instanceof BookDispatch => ['name' => $model->dispatch_number, 'sub' => $model->bookRequest?->destination_name],
            default                        => ['name' => class_basename($model).' #'.$model->getKey(), 'sub' => null],
        };
    }

    /**
     * Everything a label template needs for one sticker.
     *
     * @return array{qr: string, name: string, sub: string|null, url: string}
     */
    public function label(Model $model, int $size = 320): array
    {
        $caption = $this->caption($model);

        return [
            'qr'   => $this->dataUri($model, $size),
            'name' => $caption['name'],
            'sub'  => $caption['sub'],
            'url'  => $this->payload($model),
        ];
    }

    /**
     * Build a sheet of labels for bulk printing.
     *
     * @param  iterable<Model>  $models
     * @return array<int, array{qr: string, name: string, sub: string|null, url: string}>
     */
    public function sheet(iterable $models, int $size = 260): array
    {
        $labels = [];

        foreach ($models as $model) {
            $labels[] = $this->label($model, $size);
        }

        return $labels;
    }

    /**
     * Find whatever a scanned hash belongs to.
     *
     * @return array{type: string, model: Model}|null
     */
    public function resolve(string $hash): ?array
    {
        foreach (self::SCANNABLE as $type => $class) {
            /** @var Model|null $model */
            $model = $class::query()->where('tracking_hash', $hash)->first();

            if ($model) {
                return ['type' => $type, 'model' => $model];
            }
        }

        return null;
    }
}
