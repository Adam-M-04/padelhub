import { playerName } from '../labels.js'

export default function RankingTable({ ranking }) {
  if (ranking.length === 0) {
    return <p className="muted">Brak wyników.</p>
  }

  return (
    <table className="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Gracz</th>
          <th>Punkty</th>
          <th>Mecze</th>
          <th>Wygrane</th>
        </tr>
      </thead>
      <tbody>
        {ranking.map((row) => (
          <tr key={row.player.id}>
            <td>{row.position}</td>
            <td>{playerName(row.player)}</td>
            <td>{row.points}</td>
            <td>{row.matches}</td>
            <td>{row.wins}</td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}
