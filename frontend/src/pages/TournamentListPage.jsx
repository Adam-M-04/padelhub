import { useEffect, useState } from 'react'
import { Link } from 'react-router'
import { apiFetch } from '../api.js'
import { formatDate, formatLabels, statusLabels } from '../labels.js'

export default function TournamentListPage() {
  const [tournaments, setTournaments] = useState(null)
  const [error, setError] = useState(false)

  useEffect(() => {
    apiFetch('/tournaments')
      .then(setTournaments)
      .catch(() => setError(true))
  }, [])

  if (error) {
    return <p className="error">Nie udało się pobrać turniejów.</p>
  }
  if (tournaments === null) {
    return <p>Ładowanie…</p>
  }

  return (
    <>
      <h1>Turnieje</h1>
      {tournaments.length === 0 ? (
        <p>Nie ma jeszcze żadnych turniejów.</p>
      ) : (
        <div className="tournament-list">
          {tournaments.map((tournament) => (
            <Link key={tournament.id} to={`/tournaments/${tournament.id}`} className="card tournament-item">
              <div>
                <h2>{tournament.name}</h2>
                <p className="muted">
                  {formatLabels[tournament.format]} · {formatDate(tournament.startsAt)} · do {tournament.maxPlayers} graczy
                </p>
              </div>
              <span className={`status status-${tournament.status.toLowerCase()}`}>
                {statusLabels[tournament.status]}
              </span>
            </Link>
          ))}
        </div>
      )}
    </>
  )
}
