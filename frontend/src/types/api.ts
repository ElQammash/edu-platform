/** Laravel API Resource wrapper: { data: ... } */
export interface ApiResource<T> {
  data: T
}

/** Laravel paginated resource collection. */
export interface Paginated<T> {
  data: T[]
  links: { first: string | null; last: string | null; prev: string | null; next: string | null }
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
  }
}

/** Body of a Laravel 422 validation error response. */
export interface ValidationErrorBody {
  message: string
  errors: Record<string, string[]>
}
