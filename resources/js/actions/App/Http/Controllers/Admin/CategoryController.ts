import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\CategoryController::index
 * @see app/Http/Controllers/Admin/CategoryController.php:17
 * @route '/admin/categories'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/categories',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\CategoryController::index
 * @see app/Http/Controllers/Admin/CategoryController.php:17
 * @route '/admin/categories'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\CategoryController::index
 * @see app/Http/Controllers/Admin/CategoryController.php:17
 * @route '/admin/categories'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\CategoryController::index
 * @see app/Http/Controllers/Admin/CategoryController.php:17
 * @route '/admin/categories'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\CategoryController::index
 * @see app/Http/Controllers/Admin/CategoryController.php:17
 * @route '/admin/categories'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\CategoryController::index
 * @see app/Http/Controllers/Admin/CategoryController.php:17
 * @route '/admin/categories'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\CategoryController::index
 * @see app/Http/Controllers/Admin/CategoryController.php:17
 * @route '/admin/categories'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
* @see \App\Http\Controllers\Admin\CategoryController::create
 * @see app/Http/Controllers/Admin/CategoryController.php:27
 * @route '/admin/categories/create'
 */
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/admin/categories/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\CategoryController::create
 * @see app/Http/Controllers/Admin/CategoryController.php:27
 * @route '/admin/categories/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\CategoryController::create
 * @see app/Http/Controllers/Admin/CategoryController.php:27
 * @route '/admin/categories/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\CategoryController::create
 * @see app/Http/Controllers/Admin/CategoryController.php:27
 * @route '/admin/categories/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\CategoryController::create
 * @see app/Http/Controllers/Admin/CategoryController.php:27
 * @route '/admin/categories/create'
 */
    const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: create.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\CategoryController::create
 * @see app/Http/Controllers/Admin/CategoryController.php:27
 * @route '/admin/categories/create'
 */
        createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: create.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\CategoryController::create
 * @see app/Http/Controllers/Admin/CategoryController.php:27
 * @route '/admin/categories/create'
 */
        createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: create.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    create.form = createForm
/**
* @see \App\Http\Controllers\Admin\CategoryController::store
 * @see app/Http/Controllers/Admin/CategoryController.php:32
 * @route '/admin/categories'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/categories',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\CategoryController::store
 * @see app/Http/Controllers/Admin/CategoryController.php:32
 * @route '/admin/categories'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\CategoryController::store
 * @see app/Http/Controllers/Admin/CategoryController.php:32
 * @route '/admin/categories'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Admin\CategoryController::store
 * @see app/Http/Controllers/Admin/CategoryController.php:32
 * @route '/admin/categories'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\CategoryController::store
 * @see app/Http/Controllers/Admin/CategoryController.php:32
 * @route '/admin/categories'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Admin\CategoryController::edit
 * @see app/Http/Controllers/Admin/CategoryController.php:42
 * @route '/admin/categories/{category}/edit'
 */
export const edit = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/admin/categories/{category}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\CategoryController::edit
 * @see app/Http/Controllers/Admin/CategoryController.php:42
 * @route '/admin/categories/{category}/edit'
 */
edit.url = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { category: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'slug' in args) {
            args = { category: args.slug }
        }
    
    if (Array.isArray(args)) {
        args = {
                    category: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        category: typeof args.category === 'object'
                ? args.category.slug
                : args.category,
                }

    return edit.definition.url
            .replace('{category}', parsedArgs.category.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\CategoryController::edit
 * @see app/Http/Controllers/Admin/CategoryController.php:42
 * @route '/admin/categories/{category}/edit'
 */
edit.get = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\CategoryController::edit
 * @see app/Http/Controllers/Admin/CategoryController.php:42
 * @route '/admin/categories/{category}/edit'
 */
edit.head = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\CategoryController::edit
 * @see app/Http/Controllers/Admin/CategoryController.php:42
 * @route '/admin/categories/{category}/edit'
 */
    const editForm = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: edit.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\CategoryController::edit
 * @see app/Http/Controllers/Admin/CategoryController.php:42
 * @route '/admin/categories/{category}/edit'
 */
        editForm.get = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: edit.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\CategoryController::edit
 * @see app/Http/Controllers/Admin/CategoryController.php:42
 * @route '/admin/categories/{category}/edit'
 */
        editForm.head = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: edit.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    edit.form = editForm
/**
* @see \App\Http\Controllers\Admin\CategoryController::update
 * @see app/Http/Controllers/Admin/CategoryController.php:49
 * @route '/admin/categories/{category}'
 */
export const update = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/admin/categories/{category}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\Admin\CategoryController::update
 * @see app/Http/Controllers/Admin/CategoryController.php:49
 * @route '/admin/categories/{category}'
 */
update.url = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { category: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'slug' in args) {
            args = { category: args.slug }
        }
    
    if (Array.isArray(args)) {
        args = {
                    category: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        category: typeof args.category === 'object'
                ? args.category.slug
                : args.category,
                }

    return update.definition.url
            .replace('{category}', parsedArgs.category.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\CategoryController::update
 * @see app/Http/Controllers/Admin/CategoryController.php:49
 * @route '/admin/categories/{category}'
 */
update.put = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})
/**
* @see \App\Http\Controllers\Admin\CategoryController::update
 * @see app/Http/Controllers/Admin/CategoryController.php:49
 * @route '/admin/categories/{category}'
 */
update.patch = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\Admin\CategoryController::update
 * @see app/Http/Controllers/Admin/CategoryController.php:49
 * @route '/admin/categories/{category}'
 */
    const updateForm = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\CategoryController::update
 * @see app/Http/Controllers/Admin/CategoryController.php:49
 * @route '/admin/categories/{category}'
 */
        updateForm.put = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
            /**
* @see \App\Http\Controllers\Admin\CategoryController::update
 * @see app/Http/Controllers/Admin/CategoryController.php:49
 * @route '/admin/categories/{category}'
 */
        updateForm.patch = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
/**
* @see \App\Http\Controllers\Admin\CategoryController::destroy
 * @see app/Http/Controllers/Admin/CategoryController.php:59
 * @route '/admin/categories/{category}'
 */
export const destroy = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/admin/categories/{category}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Admin\CategoryController::destroy
 * @see app/Http/Controllers/Admin/CategoryController.php:59
 * @route '/admin/categories/{category}'
 */
destroy.url = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { category: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'slug' in args) {
            args = { category: args.slug }
        }
    
    if (Array.isArray(args)) {
        args = {
                    category: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        category: typeof args.category === 'object'
                ? args.category.slug
                : args.category,
                }

    return destroy.definition.url
            .replace('{category}', parsedArgs.category.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\CategoryController::destroy
 * @see app/Http/Controllers/Admin/CategoryController.php:59
 * @route '/admin/categories/{category}'
 */
destroy.delete = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Admin\CategoryController::destroy
 * @see app/Http/Controllers/Admin/CategoryController.php:59
 * @route '/admin/categories/{category}'
 */
    const destroyForm = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\CategoryController::destroy
 * @see app/Http/Controllers/Admin/CategoryController.php:59
 * @route '/admin/categories/{category}'
 */
        destroyForm.delete = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const CategoryController = { index, create, store, edit, update, destroy }

export default CategoryController