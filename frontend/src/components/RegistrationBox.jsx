import { useState } from 'react'
import { Link, useLocation } from 'react-router'
import { apiFetch } from '../api.js'
import { useAuth } from '../auth/useAuth.js'

export default function RegistrationBox({ tournament, registrations, onChanged }) {
  const { user } = useAuth()
  const location = useLocation()
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  if (tournament.status !== 'OPEN') {
    return null
  }

  if (!user) {
    return (
      <p>
        <Link to="/login" state={{ from: location.pathname }}>Zaloguj się</Link>, żeby zapisać się na turniej.
      </p>
    )
  }

  const isRegistered = registrations.registered.some((registration) => registration.player.id === user.id)
  const reserveIndex = registrations.reserve.findIndex((registration) => registration.player.id === user.id)
  const isFull = registrations.registered.length >= tournament.maxPlayers

  async function send(method) {
    setError('')
    setSubmitting(true)

    try {
      await apiFetch(`/tournaments/${tournament.id}/registration`, { method })
    } catch (e) {
      if (e.code === 'REGISTRATION_CLOSED') {
        setError('Zapisy na ten turniej są już zamknięte.')
      } else if (e.code !== 'ALREADY_REGISTERED' && e.code !== 'NOT_FOUND') {
        setError('Nie udało się zapisać zmiany. Spróbuj ponownie.')
      }
    }

    setSubmitting(false)
    onChanged()
  }

  return (
    <div className="registration-box">
      {isRegistered && <p>Jesteś na liście zapisanych graczy.</p>}
      {reserveIndex >= 0 && <p>Jesteś na liście rezerwowej (miejsce {reserveIndex + 1}).</p>}

      {isRegistered || reserveIndex >= 0 ? (
        <button type="button" className="button-danger" onClick={() => send('DELETE')} disabled={submitting}>
          Wypisz się
        </button>
      ) : (
        <>
          {isFull && <p>Wszystkie miejsca są zajęte, trafisz na listę rezerwową.</p>}
          <button type="button" onClick={() => send('POST')} disabled={submitting}>
            {isFull ? 'Zapisz się na listę rezerwową' : 'Zapisz się'}
          </button>
        </>
      )}

      {error && <p className="error">{error}</p>}
    </div>
  )
}
