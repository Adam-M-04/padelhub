import { useEffect, useState } from 'react'
import { apiFetch, getToken, removeToken, setToken, setUnauthorizedHandler } from '../api.js'
import { AuthContext } from './AuthContext.js'

export default function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(getToken() !== null)

  useEffect(() => {
    setUnauthorizedHandler(() => setUser(null))

    if (getToken() === null) {
      return
    }

    apiFetch('/auth/me')
      .then(setUser)
      .catch(() => removeToken())
      .finally(() => setLoading(false))
  }, [])

  async function login(email, password) {
    const data = await apiFetch('/auth/login', { method: 'POST', body: { email, password } })
    setToken(data.token)
    setUser(await apiFetch('/auth/me'))
  }

  async function register(form) {
    await apiFetch('/auth/register', { method: 'POST', body: form })
    await login(form.email, form.password)
  }

  function logout() {
    removeToken()
    setUser(null)
  }

  return (
    <AuthContext value={{ user, loading, login, register, logout }}>
      {children}
    </AuthContext>
  )
}
