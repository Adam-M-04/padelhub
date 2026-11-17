const TOKEN_KEY = 'padelhub_token'

let onUnauthorized = () => {}

export class ApiError extends Error {
  constructor(status, code, message, details = []) {
    super(message)
    this.status = status
    this.code = code
    this.details = details
  }
}

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token) {
  localStorage.setItem(TOKEN_KEY, token)
}

export function removeToken() {
  localStorage.removeItem(TOKEN_KEY)
}

export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

export async function apiFetch(path, { method = 'GET', body } = {}) {
  const token = getToken()
  const headers = { Accept: 'application/json' }

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  let response
  try {
    response = await fetch(`/api${path}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    })
  } catch {
    throw new ApiError(0, 'NETWORK_ERROR', 'Brak połączenia z serwerem')
  }

  if (response.status === 204) {
    return null
  }

  const data = await response.json().catch(() => null)

  if (!response.ok) {
    if (response.status === 401 && token) {
      removeToken()
      onUnauthorized()
      return apiFetch(path, { method, body })
    }

    const error = data?.error
    throw new ApiError(
      response.status,
      error?.code ?? 'HTTP_ERROR',
      error?.message ?? 'Nieoczekiwany błąd serwera',
      error?.details ?? [],
    )
  }

  return data
}
