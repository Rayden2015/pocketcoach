import 'package:http/http.dart' as http;

/// Invokes [onUnauthorized] when a response is HTTP 401 and the request
/// included an `Authorization` header (so login/register without a bearer are ignored).
class UnauthorizedInterceptingClient extends http.BaseClient {
  UnauthorizedInterceptingClient(this._inner, this.onUnauthorized);

  final http.Client _inner;
  final void Function() onUnauthorized;

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) async {
    final response = await _inner.send(request);
    if (response.statusCode == 401) {
      final auth = request.headers['Authorization'];
      if (auth != null && auth.isNotEmpty) {
        onUnauthorized();
      }
    }
    return response;
  }

  @override
  void close() {
    _inner.close();
  }
}
