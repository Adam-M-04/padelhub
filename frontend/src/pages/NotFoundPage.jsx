import { Link } from 'react-router'

export default function NotFoundPage() {
  return (
    <div className="card">
      <h1>Nie znaleziono strony</h1>
      <p><Link to="/">Wróć na stronę główną</Link></p>
    </div>
  )
}
