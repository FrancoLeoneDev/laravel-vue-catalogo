import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\StockMovementController::index
 * @see app/Http/Controllers/Admin/StockMovementController.php:19
 * @route '/admin/movements'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/movements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\StockMovementController::index
 * @see app/Http/Controllers/Admin/StockMovementController.php:19
 * @route '/admin/movements'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\StockMovementController::index
 * @see app/Http/Controllers/Admin/StockMovementController.php:19
 * @route '/admin/movements'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\StockMovementController::index
 * @see app/Http/Controllers/Admin/StockMovementController.php:19
 * @route '/admin/movements'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\StockMovementController::index
 * @see app/Http/Controllers/Admin/StockMovementController.php:19
 * @route '/admin/movements'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\StockMovementController::index
 * @see app/Http/Controllers/Admin/StockMovementController.php:19
 * @route '/admin/movements'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\StockMovementController::index
 * @see app/Http/Controllers/Admin/StockMovementController.php:19
 * @route '/admin/movements'
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
* @see \App\Http\Controllers\Admin\StockMovementController::store
 * @see app/Http/Controllers/Admin/StockMovementController.php:48
 * @route '/admin/movements'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/movements',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\StockMovementController::store
 * @see app/Http/Controllers/Admin/StockMovementController.php:48
 * @route '/admin/movements'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\StockMovementController::store
 * @see app/Http/Controllers/Admin/StockMovementController.php:48
 * @route '/admin/movements'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Admin\StockMovementController::store
 * @see app/Http/Controllers/Admin/StockMovementController.php:48
 * @route '/admin/movements'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\StockMovementController::store
 * @see app/Http/Controllers/Admin/StockMovementController.php:48
 * @route '/admin/movements'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
const movements = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
}

export default movements