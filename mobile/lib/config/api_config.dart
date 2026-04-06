/// Laravel API root including `/api` prefix, e.g. `http://127.0.0.1:8000/api`.
class ApiConfig {
  ApiConfig._();

  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://127.0.0.1:8000/api',
  );

  /// Same as Laravel `GOOGLE_CLIENT_ID` (Web application OAuth client). Required for `id_token` on mobile.
  static const String googleServerClientId = String.fromEnvironment(
    'GOOGLE_SERVER_CLIENT_ID',
    defaultValue: '',
  );
}
