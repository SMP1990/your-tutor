<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HeroPicture;
use App\Support\HeroPictureException;
use Illuminate\Http\Request;
use Throwable;

/**
 * Admin: add, replace, describe or delete the picture on the Home page hero (see App\Support\HeroPicture).
 * Almost any picture works: it is converted and resized automatically. Only a file that is not a picture (or an
 * absurdly large one) is refused, with the reason in plain words.
 */
class HeroPictureController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validateWithBag('hero', [
            'picture' => ['required', 'file', 'max:'.HeroPicture::MAX_UPLOAD_KB],
            'alt' => ['nullable', 'string', 'max:'.HeroPicture::ALT_MAX],
        ], [
            'picture.required' => 'Choose a picture first.',
            'picture.file' => 'The picture could not be uploaded. Please try again.',
            'picture.uploaded' => 'The picture could not be uploaded (it may be too big for the server). Please try a smaller one.',
            'picture.max' => 'This file is bigger than '.(HeroPicture::MAX_UPLOAD_KB / 1024).' MB. Please make it smaller first.',
        ]);

        try {
            HeroPicture::store($request->file('picture'), $data['alt'] ?? null);
        } catch (HeroPictureException $e) {
            return redirect()->route('admin.settings.edit')->withErrors(['picture' => $e->getMessage()], 'hero');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.settings.edit')->withErrors(['picture' => 'That picture could not be used. Please try another one.'], 'hero');
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
