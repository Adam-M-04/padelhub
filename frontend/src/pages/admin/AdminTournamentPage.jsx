import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router'
import { apiFetch } from '../../api.js'
import MatchScoreForm from '../../components/MatchScoreForm.jsx'
import RankingTable from '../../components/RankingTable.jsx'
import { errorMessage, formatDate, formatLabels, playerName, statusLabels, teamNames } from '../../labels.js'

export default function AdminTournamentPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [tournament, setTournament] = useState(null)
  const [registrations, setRegistrations] = useState(null)
  const [rounds, setRounds] = useState([])
  const [ranking, setRanking] = useState([])
  const [loadError, setLoadError] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [reloadKey, setReloadKey] = useState(0)

  useEffect(() => {
    async function load() {
      const tournamentData = await apiFetch(`/admin/tournaments/${id}`)
      setTournament(tournamentData)

      if (tournamentData.status !== 'DRAFT') {
        const [registrationsData, roundsData, rankingData] = await Promise.all([
          apiFetch(`/tournaments/${id}/registrations`),
          apiFetch(`/tournaments/${id}/rounds`),
          apiFetch(`/tournaments/${id}/ranking`),
        ])
        setRegistrations(registrationsData)
        setRounds(roundsData)
        setRanking(rankingData)
      }
    }

    load().catch((e) => setLoadError(e.status === 404 ? 'Nie znaleziono turnieju.' : errorMessage(e)))
  }, [id, reloadKey])

  function reload() {
    setReloadKey(reloadKey + 1)
  }

  async function runAction(path, method, question) {
    if (question && !window.confirm(question)) {
      return
    }

    setError('')
    setSubmitting(true)
    try {
      await apiFetch(`/admin/tournaments/${id}${path}`, { method })
      if (method === 'DELETE') {
        navigate('/admin/tournaments')
        return
      }
      reload()
    } catch (e) {
      setError(errorMessage(e))
    }
    setSubmitting(false)
  }

  if (loadError) {
    return <p className="error">{loadError}</p>
  }
  if (tournament === null) {
    return <p>Ładowanie…</p>
  }

  const status = tournament.status
  const registeredCount = registrations ? registrations.registered.length : 0

  return (
    <>
      <p><Link to="/admin/tournaments">← Turnieje</Link></p>

      <div className="card">
        <div className="title-row">
          <h1>{tournament.name}</h1>
          <span className={`status status-${status.toLowerCase()}`}>{statusLabels[status]}</span>
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

        <div className="actions toolbar">
          {(status === 'DRAFT' || status === 'OPEN') && (
            <Link to={`/admin/tournaments/${id}/edit`} className="button-link button-light">Edytuj</Link>
          )}
          {status === 'DRAFT' && (
            <>
              <button type="button" onClick={() => runAction('/open', 'POST')} disabled={submitting}>Otwórz zapisy</button>
              <button type="button" className="button-danger" onClick={() => runAction('', 'DELETE', 'Usunąć ten turniej?')} disabled={submitting}>Usuń</button>
            </>
          )}
          {status === 'OPEN' && (
            <button
              type="button"
              onClick={() => runAction('/start', 'POST', `Rozpocząć turniej z ${registeredCount} graczami? Zapisy zostaną zamknięte.`)}
              disabled={submitting}
            >
              Rozpocznij turniej
            </button>
          )}
          {status === 'IN_PROGRESS' && (
            <>
              <button type="button" onClick={() => runAction('/rounds', 'POST')} disabled={submitting}>Następna runda</button>
              <button type="button" className="button-danger" onClick={() => runAction('/finish', 'POST', 'Zakończyć turniej?')} disabled={submitting}>
                Zakończ turniej
              </button>
            </>
          )}
          {status !== 'DRAFT' && <Link to={`/tournaments/${id}`}>Strona publiczna</Link>}
        </div>
        {error && <p className="error">{error}</p>}
      </div>

      {rounds.length > 0 && (
        <section className="card">
          <h2>Ranking</h2>
          <RankingTable ranking={ranking} />
        </section>
      )}

      {[...rounds].reverse().map((round) => (
        <section key={round.number} className="card">
          <h2>Runda {round.number} {round.status === 'CLOSED' && <span className="muted">(zamknięta)</span>}</h2>
          <div className="match-list">
            {round.matches.map((match) => (
              <div key={match.id} className="match">
                <p className="muted">{match.court.name}</p>
                <p>
                  <strong>{teamNames(match.team1)}</strong> vs <strong>{teamNames(match.team2)}</strong>
                </p>
                <p>Wynik: {match.team1Score !== null ? `${match.team1Score} : ${match.team2Score}` : 'brak'}</p>
                {status === 'IN_PROGRESS' && (
                  <MatchScoreForm match={match} pointsPerMatch={tournament.pointsPerMatch} onSaved={reload} />
                )}
              </div>
            ))}
          </div>
        </section>
      ))}

      {registrations && (
        <section className="card">
          <h2>Zapisani gracze ({registeredCount}/{tournament.maxPlayers})</h2>
          {registeredCount === 0 ? (
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
      )}
    </>
  )
}
