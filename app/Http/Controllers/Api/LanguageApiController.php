<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Http\Resources\LanguageResource;
use Illuminate\Support\Facades\Validator;

class LanguageApiController extends Controller
{
    public function index()
    {
        $languages =  LanguageResource::collection(Language::all());
        return apiResponse(true,"success",$languages);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:5|unique:languages,code',
            'name' => 'required|string|max:255',
        ]);

        $language = Language::create($data);

        return new LanguageResource($language);
    }

    public function show(Language $language)
    {
        return new LanguageResource($language);
    }

    public function update(Request $request, Language $language)
    {
        $data = $request->validate([
            'code' => 'required|string|max:5|unique:languages,code,' . $language->id,
            'name' => 'required|string|max:255',
        ]);

        $language->update($data);

        return new LanguageResource($language);
    }

    public function destroy(Language $language)
    {
        $language->delete();

        return response()->noContent();
    }
}
