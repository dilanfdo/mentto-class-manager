export const lkr = (n: number) => `LKR ${Math.round(n).toLocaleString('en-LK')}`

export const DAYS = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

export const thisMonth = () => new Date().toISOString().slice(0, 7)

export const today = () => new Date().toISOString().slice(0, 10)

export function monthLabel(month: string) {
  const [y, m] = month.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleString('en-GB', { month: 'long', year: 'numeric' })
}
