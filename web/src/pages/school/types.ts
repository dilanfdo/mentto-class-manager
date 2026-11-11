export type ClassGroup = {
  id: number
  name: string
  subject: string | null
  mode: 'online' | 'physical'
  day_of_week: number | null
  start_time: string | null
  monthly_fee: number
  meeting_link?: string | null
  hall?: string | null
  students_count: number
}

export type Student = {
  id: number
  name: string
  phone: string | null
  parent_name: string | null
  parent_phone: string | null
  grade: string | null
  status: 'active' | 'left'
  paid_this_month?: boolean
  class_groups: { id: number; name: string }[]
}

export type Payment = {
  id: number
  month: string
  amount: number
  method: 'bank' | 'cash' | 'card'
  status: 'pending' | 'approved'
  created_at: string
  student: { id: number; name: string }
  class_group: { id: number; name: string }
}
