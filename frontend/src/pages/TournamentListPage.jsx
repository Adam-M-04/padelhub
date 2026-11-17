import { useEffect, useState } from 'react'
import { Link } from 'react-router'
import { apiFetch } from '../api.js'
import { formatDate, formatLabels, statusLabels } from '../labels.js'

const PAGE_SIZE = 5

export default function TournamentListPage() {
  const [result, setResult] = useState(null)
  const [error, setError] = useState(false)
  const [page, setPage] = useState(1)
  const [searchInput, setSearchInput] = useState('')
  const [filters, setFilters] = useState({ search: '', status: '', format: '' })

  useEffect(() => {
    const params = new URLSearchParams({ page, limit: PAGE_SIZE })
    for (const [key, value] of Object.entries(filters)) {
      if (value !== '') {
        params.set(key, value)
      }
    }

    apiFetch(`/tournaments?${params}`)
      .then((data) => {
        setResult(data)
        setError(false)
      })
      .catch(() => setError(true))
  }, [page, filters])

  function changeFilter(key, value) {
    setFilters({ ...filters, [key]: value })
    setPage(1)
  }

  function handleSearch(event) {
    event.preventDefault()
    changeFilter('search', searchInput.trim())
  }

  return (
    <>
      <h1>Turnieje</h1>

      <form className="filters" onSubmit={handleSearch}>
        <input
          type="search"
          placeholder="Szukaj po nazwie"
          value={searchInput}
          onChange={(event) => setSearchInput(event.target.value)}
        />
        <button type="submit">Szukaj</button>
        <select value={filters.status} onChange={(event) => changeFilter('status', event.target.value)}>
          <option value="">Wszystkie statusy</option>
          <option value="OPEN">{statusLabels.OPEN}</option>
          <option value="IN_PROGRESS">{statusLabels.IN_PROGRESS}</option>
          <option value="FINISHED">{statusLabels.FINISHED}</option>
        </select>
        <select value={filters.format} onChange={(event) => changeFilter('format', event.target.value)}>
          <option value="">Wszystkie formaty</option>
          <option value="AMERICANO">{formatLabels.AMERICANO}</option>
          <option value="MEXICANO">{formatLabels.MEXICANO}</option>
        </select>
      </form>

      {error && <p className="error">Nie udało się pobrać turniejów.</p>}
      {!error && result === null && <p>Ładowanie…</p>}
      {!error && result !== null && (
        <>
          {result.items.length === 0 ? (
            <p>Nie znaleziono turniejów.</p>
          ) : (
            <div className="tournament-list">
              {result.items.map((tournament) => (
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

          {result.pages > 1 && (
            <div className="actions pagination">
              <button type="button" className="button-light" onClick={() => setPage(page - 1)} disabled={page === 1}>
                Poprzednia
              </button>
              <span>Strona {result.page} z {result.pages}</span>
              <button type="button" className="button-light" onClick={() => setPage(page + 1)} disabled={page === result.pages}>
                Następna
              </button>
            </div>
          )}
        </>
      )}
    </>
  )
}
