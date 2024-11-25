<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Category;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get categories
        $categories = Category::when(request()->q, function ($categories) {
            $categories = $categories->where('name', 'like', '%' . request()->q . '%');
        })->latest()->paginate(5);

        // Return with Api Resource
        return new CategoryResource(true, 'List Data Categories', $categories);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'image'  => 'nullable|image|mimes:jpeg,jpg,png|max:2000', // Make image field nullable
            'name'   => 'required|unique:categories',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check if image exists
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image->storeAs('public/categories', $image->hashName());
            $imageName = $image->hashName();
        } else {
            $imageName = null; // Set to null if no image is uploaded
        }

        // Create category
        $category = Category::create([
            'image' => $imageName,  // Image can be null
            'name'  => $request->name,
            'slug'  => Str::slug($request->name, '-'),
        ]);

        if ($category) {
            // Return success with Api Resource
            return new CategoryResource(true, 'Data Category Berhasil Disimpan!', $category);
        }

        // Return failed with Api Resource
        return new CategoryResource(false, 'Data Category Gagal Disimpan!', null);
    }

    /**
     * Display the specified resource.
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = Category::whereId($id)->first();

        if ($category) {
            // Return success with Api Resource
            return new CategoryResource(true, 'Detail Data Category!', $category);
        }

        // Return failed with Api Resource
        return new CategoryResource(false, 'Detail Data Category Tidak Ditemukan!', null);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'name'     => 'required|unique:categories,name,' . $category->id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check if image is updated
        if ($request->file('image')) {
            // Remove old image
            Storage::disk('local')->delete('public/categories/' . basename($category->image));

            // Upload new image
            $image = $request->file('image');
            $image->storeAs('public/categories', $image->hashName());

            // Update category with new image
            $category->update([
                'image' => $image->hashName(),
                'name'  => $request->name,
                'slug'  => Str::slug($request->name, '-'),
            ]);
        }

        // Update category without image
        $category->update([
            'name'  => $request->name,
            'slug'  => Str::slug($request->name, '-'),
        ]);

        if ($category) {
            // Return success with Api Resource
            return new CategoryResource(true, 'Data Category Berhasil Diupdate!', $category);
        }

        // Return failed with Api Resource
        return new CategoryResource(false, 'Data Category Gagal Diupdate!', null);
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        // Remove image
        Storage::disk('local')->delete('public/categories/' . basename($category->image));

        if ($category->delete()) {
            // Return success with Api Resource
            return new CategoryResource(true, 'Data Category Berhasil Dihapus!', null);
        }

        // Return failed with Api Resource
        return new CategoryResource(false, 'Data Category Gagal Dihapus!', null);
    }
}
