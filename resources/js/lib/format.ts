const currency = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 2,
});

const integer = new Intl.NumberFormat('es-AR');

const dateTime = new Intl.DateTimeFormat('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

const dateOnly = new Intl.DateTimeFormat('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

export function formatPrice(value: string | number): string {
    return currency.format(
        typeof value === 'string' ? Number.parseFloat(value) : value,
    );
}

export function formatNumber(value: number): string {
    return integer.format(value);
}

export function formatDateTime(value: string): string {
    return dateTime.format(new Date(value));
}

export function formatDate(value: string): string {
    return dateOnly.format(new Date(value));
}
