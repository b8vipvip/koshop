const BEIJING_TIME_ZONE = 'Asia/Shanghai'

function parseTime(value: string | number | Date): Date | null {
  const date = value instanceof Date ? value : new Date(value)
  return Number.isNaN(date.getTime()) ? null : date
}

export function formatBeijingTime(value: string | number | Date): string {
  const date = parseTime(value)
  return date ? new Intl.DateTimeFormat('zh-CN', { timeZone: BEIJING_TIME_ZONE, hour12: false, hour: '2-digit', minute: '2-digit' }).format(date) : String(value ?? '')
}

export function formatBeijingDate(value: string | number | Date, locale = 'zh-CN'): string {
  const date = parseTime(value)
  return date ? new Intl.DateTimeFormat(locale, { timeZone: BEIJING_TIME_ZONE, year: 'numeric', month: 'long', day: 'numeric' }).format(date) : String(value ?? '')
}

export function formatBeijingDateTime(value: string | number | Date): string {
  const date = parseTime(value)
  return date ? new Intl.DateTimeFormat('zh-CN', { timeZone: BEIJING_TIME_ZONE, hour12: false, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(date) : String(value ?? '')
}
