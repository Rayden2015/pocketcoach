/// Laravel API root including `/api` prefix, e.g. `http://127.0.0.1:8000/api`.
class ApiConfig {
  ApiConfig._();

  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://127.0.0.1:8000/api',
  );

  /// Optional override for opening coach / web flows in the browser (same origin as the app, without `/api`).
  /// When empty, derived from [baseUrl] by stripping a trailing `/api`.
  static const String webBaseUrlOverride = String.fromEnvironment(
    'WEB_BASE_URL',
    defaultValue: '',
  );

  /// Web origin for `url_launcher` (e.g. `http://127.0.0.1:8000`).
  static String get webBaseUrl {
    final o = webBaseUrlOverride.trim();
    if (o.isNotEmpty) {
      return o.endsWith('/') ? o.substring(0, o.length - 1) : o;
    }
    var b = baseUrl.trim();
    if (b.endsWith('/')) {
      b = b.substring(0, b.length - 1);
    }
    if (b.endsWith('/api')) {
      b = b.substring(0, b.length - 4);
    }
    return b;
  }

  /// Same as Laravel `GOOGLE_CLIENT_ID` (Web application OAuth client). Required for `id_token` on mobile.
  static const String googleServerClientId = String.fromEnvironment(
    'GOOGLE_SERVER_CLIENT_ID',
    defaultValue: '',
  );
}
