import { useState } from 'react'
import { apiFetch } from '../api.js'

export default function MatchScoreForm({ match, pointsPerMatch, onSaved }) {
  const [team1Score, setTeam1Score] = useState(match.team1Score ?? '')
  const [team2Score, setTeam2Score] = useState(match.team2Score ?? '')
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')
  const [submitting, setSubmitting] = useState(false)

  function handleTeam1Change(event) {
    const value = event.target.value
    setTeam1Score(value)
    setSuccess('')

    if (value !== '' && Number(value) >= 0 && Number(value) <= pointsPerMatch) {
      setTeam2Score(pointsPerMatch - Number(value))
    }
  }

  function handleTeam2Change(event) {
    const value = event.target.value
    setTeam2Score(value)
    setSuccess('')

    if (value !== '' && Number(value) >= 0 && Number(value) <= pointsPerMatch) {
      setTeam1Score(pointsPerMatch - Number(value))
    }
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setError('')
    setSuccess('')

    if (Number(team1Score) + Number(team2Score) !== pointsPerMatch) {
      setError(`Suma punktów musi wynosić ${pointsPerMatch}.`)
      return
    }

    setSubmitting(true)

    try {
      await apiFetch(`/matches/${match.id}/score`, {
        method: 'PUT',
        body: { team1Score: Number(team1Score), team2Score: Number(team2Score), version: match.version },
      })
      setSuccess('Wynik zapisany.')
    } catch (e) {
      if (e.code === 'MATCH_VERSION_CONFLICT') {
        setError('Ktoś w międzyczasie zmienił wynik tego meczu. Sprawdź aktualny wynik i zapisz ponownie, jeśli trzeba.')
      } else if (e.code === 'ROUND_CLOSED') {
        setError('Runda jest już zamknięta, wynik może zmienić tylko administrator.')
      } else if (e.code === 'TOURNAMENT_NOT_IN_PROGRESS') {
        setError('Turniej nie jest w trakcie, nie można zmienić wyniku.')
      } else {
        setError('Nie udało się zapisać wyniku. Spróbuj ponownie.')
      }
    }

    setSubmitting(false)
    onSaved()
  }

  return (
    <form className="score-form" onSubmit={handleSubmit}>
      <div className="score-inputs">
        <input type="number" min="0" max={pointsPerMatch} value={team1Score} onChange={handleTeam1Change} required aria-label="Punkty pary 1" />
        <span>:</span>
        <input type="number" min="0" max={pointsPerMatch} value={team2Score} onChange={handleTeam2Change} required aria-label="Punkty pary 2" />
        <button type="submit" disabled={submitting}>Zapisz wynik</button>
      </div>
      {error && <p className="error">{error}</p>}
      {success && <p className="success">{success}</p>}
    </form>
  )
}
