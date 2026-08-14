<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/categories/Index', [
            'categories' => Category::query()
                ->withProductCount()
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/categories/Create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::query()->create($request->validated());

        return to_route('admin.categories.index')->with('toast', [
            'type' => 'success',
            'message' => 'Categoría creada.',
        ]);
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('admin/categories/Edit', [
            'category' => $category->loadCount('products'),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return to_route('admin.categories.index')->with('toast', [
            'type' => 'success',
            'message' => 'Categoría actualizada.',
        ]);
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar una categoría con productos asociados. Reasignalos primero.',
            ]);
        }

        $category->delete();

        return to_route('admin.categories.index')->with('toast', [
            'type' => 'success',
            'message' => 'Categoría eliminada.',
        ]);
    }
}
