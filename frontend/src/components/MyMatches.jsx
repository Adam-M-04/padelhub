import { useAuth } from '../auth/useAuth.js'
import { teamNames } from '../labels.js'
import MatchScoreForm from './MatchScoreForm.jsx'

export default function MyMatches({ tournament, rounds, onChanged }) {
  const { user } = useAuth()

  if (!user) {
    return null
  }

  const myMatches = []
  for (const round of rounds) {
    for (const match of round.matches) {
      const players = [...match.team1, ...match.team2]
      if (players.some((player) => player.id === user.id)) {
        myMatches.push({ round, match })
      }
    }
  }

  if (myMatches.length === 0) {
    return null
  }

  return (
    <section className="card">
      <h2>Moje mecze</h2>
      <div className="match-list">
        {myMatches.reverse().map(({ round, match }) => {
          const canEdit = tournament.status === 'IN_PROGRESS' && round.status === 'IN_PROGRESS'

          return (
            <div key={match.id} className="match">
              <p className="muted">Runda {round.number} · {match.court.name}</p>
              <p>
                <strong>{teamNames(match.team1)}</strong> vs <strong>{teamNames(match.team2)}</strong>
              </p>
              <p>
                Wynik: {match.team1Score !== null ? `${match.team1Score} : ${match.team2Score}` : 'brak'}
              </p>
              {canEdit && (
                <MatchScoreForm match={match} pointsPerMatch={tournament.pointsPerMatch} onSaved={onChanged} />
              )}
            </div>
          )
        })}
      </div>
    </section>
  )
}
