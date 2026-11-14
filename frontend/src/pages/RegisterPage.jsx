import { useState } from 'react'
import { Link, Navigate, useNavigate } from 'react-router'
import { useAuth } from '../auth/useAuth.js'

const emptyForm = { firstName: '', lastName: '', email: '', password: '' }

export default function RegisterPage() {
  const { user, loading, register } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState(emptyForm)
  const [fieldErrors, setFieldErrors] = useState({})
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  if (loading) {
    return null
  }
  if (user) {
    return <Navigate to="/" replace />
  }

  function handleChange(event) {
    setForm({ ...form, [event.target.name]: event.target.value })
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setError('')
    setFieldErrors({})
    setSubmitting(true)

    try {
      await register(form)
      navigate('/', { replace: true })
    } catch (e) {
      if (e.code === 'EMAIL_TAKEN') {
        setFieldErrors({ email: 'Ten e-mail jest już zajęty.' })
      } else if (e.code === 'VALIDATION_ERROR') {
        const errors = {}
        for (const detail of e.details) {
          errors[detail.field] = detail.message
        }
        setFieldErrors(errors)
      } else {
        setError(e.message)
      }
      setSubmitting(false)
    }
  }

  return (
    <div className="card form-card">
      <h1>Rejestracja</h1>
      <form onSubmit={handleSubmit}>
        <label>
          Imię
          <input name="firstName" value={form.firstName} onChange={handleChange} required maxLength={100} autoComplete="given-name" />
          {fieldErrors.firstName && <span className="error">{fieldErrors.firstName}</span>}
        </label>
        <label>
          Nazwisko
          <input name="lastName" value={form.lastName} onChange={handleChange} required maxLength={100} autoComplete="family-name" />
          {fieldErrors.lastName && <span className="error">{fieldErrors.lastName}</span>}
        </label>
        <label>
          E-mail
          <input type="email" name="email" value={form.email} onChange={handleChange} required maxLength={180} autoComplete="email" />
          {fieldErrors.email && <span className="error">{fieldErrors.email}</span>}
        </label>
        <label>
          Hasło (min. 10 znaków)
          <input type="password" name="password" value={form.password} onChange={handleChange} required minLength={10} maxLength={72} autoComplete="new-password" />
          {fieldErrors.password && <span className="error">{fieldErrors.password}</span>}
        </label>
        {error && <p className="error">{error}</p>}
        <button type="submit" disabled={submitting}>Zarejestruj</button>
      </form>
      <p>Masz już konto? <Link to="/login">Zaloguj się</Link></p>
    </div>
  )
}
