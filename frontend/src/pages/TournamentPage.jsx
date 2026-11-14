import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router'
import { apiFetch } from '../api.js'
import RankingTable from '../components/RankingTable.jsx'
import { formatDate, formatLabels, playerName, statusLabels } from '../labels.js'

export default function TournamentPage() {
  const { id } = useParams()
  const [tournament, setTournament] = useState(null)
  const [registrations, setRegistrations] = useState(null)
  const [ranking, setRanking] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    Promise.all([
      apiFetch(`/tournaments/${id}`),
      apiFetch(`/tournaments/${id}/registrations`),
      apiFetch(`/tournaments/${id}/ranking`),
    ])
      .then(([tournamentData, registrationsData, rankingData]) => {
        setTournament(tournamentData)
        setRegistrations(registrationsData)
        setRanking(rankingData)
      })
      .catch((e) => setError(e.status === 404 ? 'Nie znaleziono turnieju.' : 'Nie udało się pobrać turnieju.'))
  }, [id])

  if (error) {
    return (
      <div className="card">
        <p className="error">{error}</p>
        <p><Link to="/">Wróć do listy turniejów</Link></p>
      </div>
    )
  }
  if (tournament === null) {
    return <p>Ładowanie…</p>
  }

  const started = tournament.status === 'IN_PROGRESS' || tournament.status === 'FINISHED'

  return (
    <>
      <p><Link to="/">← Turnieje</Link></p>
      <div className="card">
        <div className="title-row">
          <h1>{tournament.name}</h1>
          <span className={`status status-${tournament.status.toLowerCase()}`}>
            {statusLabels[tournament.status]}
          </span>
        </div>
        <dl className="details">
          <dt>Format</dt>
          <dd>{formatLabels[tournament.format]}</dd>
          <dt>Start</dt>
          <dd>{formatDate(tournament.startsAt)}</dd>
          <dt>Pula punktów w meczu</dt>
          <dd>{tournament.pointsPerMatch}</dd>
          <dt>Maks. liczba graczy</dt>
          <dd>{tournament.maxPlayers}</dd>
          <dt>Korty</dt>
          <dd>{tournament.courts.map((court) => court.name).join(', ')}</dd>
        </dl>
      </div>

      {started && (
        <section className="card">
          <h2>Ranking</h2>
          <RankingTable ranking={ranking} />
        </section>
      )}

      <section className="card">
        <h2>Zapisani gracze ({registrations.registered.length}/{tournament.maxPlayers})</h2>
        {registrations.registered.length === 0 ? (
          <p className="muted">Nikt się jeszcze nie zapisał.</p>
        ) : (
          <ol>
            {registrations.registered.map((registration) => (
              <li key={registration.player.id}>{playerName(registration.player)}</li>
            ))}
          </ol>
        )}

        {registrations.reserve.length > 0 && (
          <>
            <h3>Lista rezerwowa</h3>
            <ol>
              {registrations.reserve.map((registration) => (
                <li key={registration.player.id}>{playerName(registration.player)}</li>
              ))}
            </ol>
          </>
        )}
      </section>
    </>
  )
}
