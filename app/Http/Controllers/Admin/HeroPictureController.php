<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HeroPicture;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Throwable;

/** Admin: add, replace, describe or delete the picture on the Home page hero (see App\Support\HeroPicture). */
class HeroPictureController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validateWithBag('hero', [
            'picture' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(HeroPicture::MAX_UPLOAD_KB)->dimensions(
                Rule::dimensions()->minWidth(HeroPicture::MIN_WIDTH)->minHeight(HeroPicture::MIN_HEIGHT)
                    ->maxWidth(HeroPicture::MAX_SIDE)->maxHeight(HeroPicture::MAX_SIDE)
            )],
            'alt' => ['nullable', 'string', 'max:'.HeroPicture::ALT_MAX],
        ], [
            'picture.required' => 'Choose a picture first.',
            'picture.max' => 'The picture is too big. Please use one under '.(HeroPicture::MAX_UPLOAD_KB / 1024).' MB.',
            'picture.dimensions' => 'The picture must be at least '.HeroPicture::MIN_WIDTH.' x '.HeroPicture::MIN_HEIGHT.' pixels (and not more than '.HeroPicture::MAX_SIDE.').',
            'picture.image' => 'Please upload a JPG, PNG or WebP picture.',
            'picture.mimes' => 'Please upload a JPG, PNG or WebP picture.',
        ]);

        try {
            HeroPicture::store($request->file('picture'), $data['alt'] ?? null);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.settings.edit')->withErrors(['picture' => 'That picture could not be used. Try another one.'], 'hero');
        }

        return redirect()->route('admin.settings.edit')->with('notice', 'The Home page picture is saved.');
    }

    /** Only the description (alt text) of the picture that is already there. */
    public function update(Request $request)
    {
        abort_unless(HeroPicture::current(), 404);

        $data = $request->validateWithBag('hero', ['alt' => ['nullable', 'string', 'max:'.HeroPicture::ALT_MAX]]);
        HeroPicture::updateAlt($data['alt'] ?? null);

        return redirect()->route('admin.settings.edit')->with('notice', 'The description is saved.');
    }

    public function destroy()
    {
        HeroPicture::remove();

        return redirect()->route('admin.settings.edit')->with('notice', 'The picture is removed. The Home page shows its default design again.');
    }
}
