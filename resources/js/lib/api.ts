type Json = Record<string, unknown>;

const xsrfToken = () =>
    decodeURIComponent(
        document.cookie
            .split('; ')
            .find((c) => c.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );

async function request<T = unknown>(method: string, url: string, body?: Json | FormData): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrfToken(),
    };

    let payload: BodyInit | undefined;
    if (body instanceof FormData) {
        payload = body;
    } else if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    const res = await fetch(url, {
        method,
        headers,
        body: payload,
        credentials: 'same-origin',
    });

    const text = await res.text();
    const json = text ? JSON.parse(text) : null;

    if (!res.ok) {
        const message = json?.message || res.statusText || 'Request failed';
        const err = new Error(message) as Error & { status: number; errors?: unknown };
        err.status = res.status;
        err.errors = json?.errors;
        throw err;
    }

    return json as T;
}

export const api = {
    get: <T = unknown>(url: string) => request<T>('GET', url),
    post: <T = unknown>(url: string, body?: Json) => request<T>('POST', url, body),
    put: <T = unknown>(url: string, body?: Json) => request<T>('PUT', url, body),
    patch: <T = unknown>(url: string, body?: Json) => request<T>('PATCH', url, body),
    delete: <T = unknown>(url: string) => request<T>('DELETE', url),
};
