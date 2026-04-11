import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:pocket_coach_mobile/config/api_config.dart';
import 'package:pocket_coach_mobile/models/catalog_models.dart';
import 'package:pocket_coach_mobile/models/continue_learning.dart';
import 'package:pocket_coach_mobile/models/course_detail.dart';
import 'package:pocket_coach_mobile/models/engagement_models.dart';
import 'package:pocket_coach_mobile/models/learning_summary.dart';

class ApiException implements Exception {
  ApiException(
    this.statusCode,
    this.body, {
    this.message,
    this.freeProductId,
  });

  final int statusCode;
  final String body;
  final String? message;
  final int? freeProductId;

  @override
  String toString() =>
      message != null ? 'ApiException($statusCode): $message' : 'ApiException($statusCode): $body';
}

/// JSON client for Pocket Coach `/api/v1` (Sanctum).
class PocketCoachApi {
  PocketCoachApi({http.Client? httpClient, String? baseUrl})
    : _client = httpClient ?? http.Client(),
      _base = baseUrl ?? ApiConfig.baseUrl;

  final http.Client _client;
  final String _base;

  Uri _u(String path) {
    final b = _base.endsWith('/') ? _base.substring(0, _base.length - 1) : _base;
    final p = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$b$p');
  }

  Map<String, String> _jsonHeaders(String? bearer) {
    final h = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    if (bearer != null && bearer.isNotEmpty) {
      h['Authorization'] = 'Bearer $bearer';
    }
    return h;
  }

  Future<Map<String, dynamic>> loginWithGoogle({
    required String idToken,
  }) async {
    final res = await _client.post(
      _u('/v1/auth/google'),
      headers: _jsonHeaders(null),
      body: jsonEncode({'id_token': idToken}),
    );
    return _decodeObject(res);
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final res = await _client.post(
      _u('/v1/login'),
      headers: _jsonHeaders(null),
      body: jsonEncode({'email': email, 'password': password}),
    );
    return _decodeObject(res);
  }

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final res = await _client.post(
      _u('/v1/register'),
      headers: _jsonHeaders(null),
      body: jsonEncode({
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
      }),
    );
    return _decodeObject(res);
  }

  Future<void> joinTenant({
    required String bearer,
    required String tenantSlug,
  }) async {
    final res = await _client.post(
      _u('/v1/tenants/$tenantSlug/join'),
      headers: _jsonHeaders(bearer),
    );
    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw _exceptionFromResponse(res);
    }
  }

  Future<void> freeEnroll({
    required String bearer,
    required String tenantSlug,
    required int productId,
  }) async {
    final res = await _client.post(
      _u('/v1/tenants/$tenantSlug/enrollments/free'),
      headers: _jsonHeaders(bearer),
      body: jsonEncode({'product_id': productId}),
    );
    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw _exceptionFromResponse(res);
    }
  }

  Future<List<LearningCourseSummary>> fetchLearningSummary({
    required String bearer,
    required String tenantSlug,
  }) async {
    final res = await _client.get(
      _u('/v1/tenants/$tenantSlug/learning-summary'),
      headers: _jsonHeaders(bearer),
    );
    final map = _decodeObject(res);
    final data = map['data'];
    if (data is! List) {
      return [];
    }
    return data
        .map((e) => LearningCourseSummary.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<void> logout(String bearer) async {
    final res = await _client.post(
      _u('/v1/logout'),
      headers: _jsonHeaders(bearer),
    );
    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException(res.statusCode, res.body);
    }
  }

  Future<List<CatalogProgram>> fetchCatalog({
    required String bearer,
    required String tenantSlug,
  }) async {
    final res = await _client.get(
      _u('/v1/tenants/$tenantSlug/catalog'),
      headers: _jsonHeaders(bearer),
    );
    final map = _decodeObject(res);
    final data = map['data'];
    if (data is! List) {
      return [];
    }
    return data
        .map((e) => CatalogProgram.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<CourseDetail> fetchCourse({
    required String bearer,
    required String tenantSlug,
    required int courseId,
  }) async {
    final res = await _client.get(
      _u('/v1/tenants/$tenantSlug/courses/$courseId'),
      headers: _jsonHeaders(bearer),
    );
    if (res.statusCode == 403) {
      throw _exceptionFromResponse(res);
    }
    final map = _decodeObjectOrThrow(res);
    final data = map['data'];
    if (data is! Map<String, dynamic>) {
      throw ApiException(res.statusCode, res.body, message: 'Invalid course payload');
    }
    return CourseDetail.fromJson(data);
  }

  Future<ContinueLearningPayload?> fetchContinue({
    required String bearer,
    required String tenantSlug,
  }) async {
    final res = await _client.get(
      _u('/v1/tenants/$tenantSlug/continue'),
      headers: _jsonHeaders(bearer),
    );
    final map = _decodeObject(res);
    final data = map['data'];
    if (data == null) {
      return null;
    }
    if (data is! Map<String, dynamic>) {
      return null;
    }
    return ContinueLearningPayload.fromJson(data);
  }

  Future<List<UserNotificationItem>> fetchNotifications({
    required String bearer,
  }) async {
    final res = await _client.get(
      _u('/v1/notifications'),
      headers: _jsonHeaders(bearer),
    );
    final map = _decodeObject(res);
    final data = map['data'];
    if (data is! List) {
      return [];
    }
    return data
        .map((e) => UserNotificationItem.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<int> fetchUnreadNotificationCount({
    required String bearer,
  }) async {
    final res = await _client.get(
      _u('/v1/notifications/unread-count'),
      headers: _jsonHeaders(bearer),
    );
    final map = _decodeObject(res);
    final c = map['count'];
    if (c is int) {
      return c;
    }
    if (c is num) {
      return c.toInt();
    }
    return 0;
  }

  /// Marks a single database notification as read (`id` is the UUID from the list endpoint).
  Future<Map<String, dynamic>> markNotificationRead({
    required String bearer,
    required String notificationId,
  }) async {
    final res = await _client.patch(
      _u('/v1/notifications/$notificationId'),
      headers: _jsonHeaders(bearer),
    );
    return _decodeObject(res);
  }

  /// Marks every unread notification as read; response includes `marked` (count updated).
  Future<int> markAllNotificationsRead({
    required String bearer,
  }) async {
    final res = await _client.post(
      _u('/v1/notifications/read-all'),
      headers: _jsonHeaders(bearer),
    );
    final map = _decodeObject(res);
    final m = map['marked'];
    if (m is int) {
      return m;
    }
    if (m is num) {
      return m.toInt();
    }
    return 0;
  }

  /// When reflections are disabled for the tenant, the API returns 404 — treated as null.
  /// When enabled but there is no prompt, returns null (`data` null).
  Future<ReflectionPrompt?> fetchReflectionLatest({
    required String bearer,
    required String tenantSlug,
  }) async {
    final res = await _client.get(
      _u('/v1/tenants/$tenantSlug/reflection-prompts/latest'),
      headers: _jsonHeaders(bearer),
    );
    if (res.statusCode == 404) {
      return null;
    }
    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw _exceptionFromResponse(res);
    }
    if (res.body.isEmpty) {
      return null;
    }
    final decoded = jsonDecode(res.body) as dynamic;
    if (decoded is! Map<String, dynamic>) {
      return null;
    }
    final data = decoded['data'];
    if (data is! Map<String, dynamic>) {
      return null;
    }
    return ReflectionPrompt.fromJson(data);
  }

  Future<ReflectionPrompt> fetchReflectionPrompt({
    required String bearer,
    required String tenantSlug,
    required int promptId,
  }) async {
    final res = await _client.get(
      _u('/v1/tenants/$tenantSlug/reflection-prompts/$promptId'),
      headers: _jsonHeaders(bearer),
    );
    final map = _decodeObjectOrThrow(res);
    final data = map['data'];
    if (data is! Map<String, dynamic>) {
      throw ApiException(res.statusCode, res.body, message: 'Invalid reflection payload');
    }
    return ReflectionPrompt.fromJson(data);
  }

  Future<void> recordReflectionView({
    required String bearer,
    required String tenantSlug,
    required int promptId,
  }) async {
    final res = await _client.post(
      _u('/v1/tenants/$tenantSlug/reflection-prompts/$promptId/view'),
      headers: _jsonHeaders(bearer),
    );
    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw _exceptionFromResponse(res);
    }
  }

  Future<Map<String, dynamic>> upsertReflectionResponse({
    required String bearer,
    required String tenantSlug,
    required int promptId,
    required String body,
    bool? isPublic,
  }) async {
    final payload = <String, dynamic>{'body': body};
    if (isPublic != null) {
      payload['is_public'] = isPublic;
    }
    final res = await _client.put(
      _u('/v1/tenants/$tenantSlug/reflection-prompts/$promptId/response'),
      headers: _jsonHeaders(bearer),
      body: jsonEncode(payload),
    );
    return _decodeObject(res);
  }

  Future<Map<String, dynamic>> updateLessonProgress({
    required String bearer,
    required String tenantSlug,
    required int lessonId,
    bool? completed,
    int? positionSeconds,
    String? notes,
    bool? notesIsPublic,
  }) async {
    final payload = <String, dynamic>{};
    if (completed != null) {
      payload['completed'] = completed;
    }
    if (positionSeconds != null) {
      payload['position_seconds'] = positionSeconds;
    }
    if (notes != null) {
      payload['notes'] = notes;
    }
    if (notesIsPublic != null) {
      payload['notes_is_public'] = notesIsPublic;
    }
    final res = await _client.put(
      _u('/v1/tenants/$tenantSlug/lessons/$lessonId/progress'),
      headers: _jsonHeaders(bearer),
      body: jsonEncode(payload),
    );
    return _decodeObject(res);
  }

  dynamic _decode(http.Response res) {
    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw _exceptionFromResponse(res);
    }
    if (res.body.isEmpty) {
      return null;
    }
    return jsonDecode(res.body) as dynamic;
  }

  Map<String, dynamic> _decodeObject(http.Response res) {
    final v = _decode(res);
    if (v is Map<String, dynamic>) {
      return v;
    }
    throw ApiException(res.statusCode, res.body);
  }

  Map<String, dynamic> _decodeObjectOrThrow(http.Response res) {
    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw _exceptionFromResponse(res);
    }
    if (res.body.isEmpty) {
      throw ApiException(res.statusCode, res.body, message: 'Empty body');
    }
    final v = jsonDecode(res.body) as dynamic;
    if (v is Map<String, dynamic>) {
      return v;
    }
    throw ApiException(res.statusCode, res.body);
  }

  ApiException _exceptionFromResponse(http.Response res) {
    String? msg;
    int? freeProductId;
    try {
      final j = jsonDecode(res.body) as dynamic;
      if (j is Map) {
        if (j['message'] is String) {
          msg = j['message'] as String;
        }
        final fp = j['free_product_id'];
        if (fp is int) {
          freeProductId = fp;
        } else if (fp is num) {
          freeProductId = fp.toInt();
        }
      }
    } catch (_) {}
    return ApiException(
      res.statusCode,
      res.body,
      message: msg,
      freeProductId: freeProductId,
    );
  }

  void close() => _client.close();
}
