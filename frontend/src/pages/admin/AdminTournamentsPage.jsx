import { useEffect, useState } from 'react'
import { Link } from 'react-router'
import { apiFetch } from '../../api.js'
import { formatDate, formatLabels, statusLabels } from '../../labels.js'

export default function AdminTournamentsPage() {
  const [tournaments, setTournaments] = useState(null)
  const [error, setError] = useState(false)

  useEffect(() => {
    apiFetch('/admin/tournaments')
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
    <div className="card">
      <div className="title-row">
        <h1>Turnieje</h1>
        <Link to="/admin/tournaments/new" className="button-link">Nowy turniej</Link>
      </div>

      {tournaments.length === 0 ? (
        <p className="muted">Nie ma jeszcze żadnych turniejów.</p>
      ) : (
        <table className="table">
          <thead>
            <tr>
              <th>Nazwa</th>
              <th>Format</th>
              <th>Start</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {tournaments.map((tournament) => (
              <tr key={tournament.id}>
                <td><Link to={`/admin/tournaments/${tournament.id}`}>{tournament.name}</Link></td>
                <td>{formatLabels[tournament.format]}</td>
                <td>{formatDate(tournament.startsAt)}</td>
                <td>
                  <span className={`status status-${tournament.status.toLowerCase()}`}>{statusLabels[tournament.status]}</span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
