export type ClientSummary = {
  id: number
  name: string
  slug: string
  type: 'tutor' | 'institute'
  status: 'active' | 'trial' | 'suspended'
  contact_name: string
  contact_email: string
  contact_phone: string | null
  created_at: string
  active_students: number
  features: string[]
  addons: string[]
  monthly_estimate: number
}

export type FeatureRow = {
  key: string
  name: string
  description: string
  group: 'core' | 'addon'
  locked: boolean
  monthly: number
  per_student: number
  enabled: boolean
  updated_at: string | null
  updated_by: string | null
}

export type ClientDetail = ClientSummary & {
  notes: string | null
  feature_list: FeatureRow[]
  users: { id: number; name: string; email: string; role: string; created_at: string }[]
  counts: { classes: number; collected_this_month: number }
}
