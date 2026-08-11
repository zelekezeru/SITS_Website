<?php

namespace App\Http\Controllers\Bookstore;

use App\Http\Controllers\Controller;
use App\Services\Bookstore\QrLabelService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * QR images and printable label sheets for every scannable thing in the module.
 *
 * One controller rather than a `qr()` method on each resource controller: the
 * behaviour is identical for all five model types, and the label sheet has to
 * be able to mix them anyway (a rack of new sections plus the titles on it).
 */
class LabelController extends Controller
{
    public function __construct(private readonly QrLabelService $qr)
    {
    }

    /**
     * A single QR image, for a quick one-off print.
     *
     * PNG when the server has imagick, SVG otherwise — both print identically,
     * and shared cPanel hosting does not always carry the extension.
     */
    public function png(string $type, int $id, Request $request): HttpResponse
    {
        $model = $this->find($type, $id);
        $size  = max(120, min(800, (int) $request->integer('size', 320)));
        $slug  = Str::slug($this->qr->caption($model)['name']) ?: $type;

        [$body, $mime, $ext] = $this->qr->supportsPng()
            ? [$this->qr->png($model, $size), 'image/png', 'png']
            : [$this->qr->svg($model, $size), 'image/svg+xml', 'svg'];

        return response($body, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => "inline; filename=\"{$slug}-qr.{$ext}\"",
            'Cache-Control'       => 'private, max-age=86400',
        ]);
    }

    /**
     * A printable sheet of labels — QR above, human-readable name directly
     * beneath, which is the half the store keeper actually reads.
     *
     * `?type=section&ids[]=1&ids[]=2`, or `?type=section&all=1` for everything
     * of that type.
     */
    public function sheet(Request $request): Response
    {
        $validated = $request->validate([
            'type'   => 'required|string|in:'.implode(',', array_keys(QrLabelService::SCANNABLE)),
            'ids'    => 'array',
            'ids.*'  => 'integer',
            'all'    => 'boolean',
            'copies' => 'integer|min:1|max:20',
        ]);

        $class = QrLabelService::SCANNABLE[$validated['type']];

        $models = $class::query()
            ->when(! ($validated['all'] ?? false), fn ($q) => $q->whereIn('id', $validated['ids'] ?? []))
            ->orderBy('id')
            ->limit(500)
            ->get();

        $labels = $this->qr->sheet($models);
        $copies = (int) ($validated['copies'] ?? 1);

        if ($copies > 1) {
            $labels = collect($labels)
                ->flatMap(fn (array $label) => array_fill(0, $copies, $label))
                ->all();
        }

        return Inertia::render('Bookstore/Labels/Sheet', [
            'labels' => $labels,
            'type'   => $validated['type'],
        ]);
    }

    protected function find(string $type, int $id): Model
    {
        abort_unless(isset(QrLabelService::SCANNABLE[$type]), 404);

        $class = QrLabelService::SCANNABLE[$type];

        return $class::query()->findOrFail($id);
    }
}
