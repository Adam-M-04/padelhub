export const formatLabels = {
  AMERICANO: 'Americano',
  MEXICANO: 'Mexicano',
}

export const statusLabels = {
  DRAFT: 'Szkic',
  OPEN: 'Zapisy otwarte',
  IN_PROGRESS: 'W trakcie',
  FINISHED: 'Zakończony',
}

export function formatDate(value) {
  return new Date(value).toLocaleString('pl-PL', { dateStyle: 'medium', timeStyle: 'short' })
}

export function playerName(player) {
  return `${player.firstName} ${player.lastName}`
}
