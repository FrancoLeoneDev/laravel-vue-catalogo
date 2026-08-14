import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\CatalogController::show
 * @see app/Http/Controllers/CatalogController.php:54
 * @route '/productos/{product}'
 */
export const show = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/productos/{product}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CatalogController::show
 * @see app/Http/Controllers/CatalogController.php:54
 * @route '/productos/{product}'
 */
show.url = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { product: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'slug' in args) {
            args = { product: args.slug }
        }
    
    if (Array.isArray(args)) {
        args = {
                    product: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        product: typeof args.product === 'object'
                ? args.product.slug
                : args.product,
                }

    return show.definition.url
            .replace('{product}', parsedArgs.product.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CatalogController::show
 * @see app/Http/Controllers/CatalogController.php:54
 * @route '/productos/{product}'
 */
show.get = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CatalogController::show
 * @see app/Http/Controllers/CatalogController.php:54
 * @route '/productos/{product}'
 */
show.head = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CatalogController::show
 * @see app/Http/Controllers/CatalogController.php:54
 * @route '/productos/{product}'
 */
    const showForm = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CatalogController::show
 * @see app/Http/Controllers/CatalogController.php:54
 * @route '/productos/{product}'
 */
        showForm.get = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CatalogController::show
 * @see app/Http/Controllers/CatalogController.php:54
 * @route '/productos/{product}'
 */
        showForm.head = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
const catalog = {
    show: Object.assign(show, show),
}

export default catalog