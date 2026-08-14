export interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    products_count?: number;
    created_at?: string;
    updated_at?: string;
}

export interface Product {
    id: number;
    category_id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    sku: string;
    is_active: boolean;
    low_stock_threshold: number;
    image_path: string | null;
    /** Present only when the query used Product::scopeWithCurrentStock(). */
    current_stock?: number;
    category?: Category;
    created_at?: string;
    updated_at?: string;
}

export type StockMovementType = 'entrada' | 'salida';

export interface StockMovement {
    id: number;
    product_id: number;
    user_id: number | null;
    type: StockMovementType;
    quantity: number;
    reason: string;
    occurred_at: string;
    product?: Pick<Product, 'id' | 'name' | 'slug' | 'sku'>;
    user?: { id: number; name: string } | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
    prev_page_url: string | null;
    next_page_url: string | null;
}
