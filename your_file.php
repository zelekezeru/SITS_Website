// ...existing code...
if ($request->hasFile('banner')) {
    $bannerPath = $request->file('banner')->store('banners', 'public');
    $data['banner'] = $bannerPath;
}

if ($request->hasFile('pdf')) {
    $pdfPath = $request->file('pdf')->store('pdfs', 'public');
    $data['pdf'] = $pdfPath;
}
// ...existing code...
