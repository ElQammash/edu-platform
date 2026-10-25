/** Laravel API Resource wrapper: { data: ... } */
export interface ApiResource<T> {
  data: T
}

/** Body of a Laravel 422 validation error response. */
export interface ValidationErrorBody {
  message: string
  errors: Record<string, string[]>
}
