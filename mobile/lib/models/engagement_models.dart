class UserNotificationItem {
  UserNotificationItem({
    required this.id,
    required this.type,
    this.data,
    this.readAt,
    this.createdAt,
    this.title,
    this.preview,
    this.url,
  });

  factory UserNotificationItem.fromJson(Map<String, dynamic> j) {
    Map<String, dynamic>? d;
    final raw = j['data'];
    if (raw is Map<String, dynamic>) {
      d = raw;
    }
    return UserNotificationItem(
      id: j['id'].toString(),
      type: j['type'] as String? ?? '',
      data: d,
      readAt: j['read_at'] as String?,
      createdAt: j['created_at'] as String?,
      title: j['title'] as String?,
      preview: j['preview'] as String?,
      url: j['url'] as String?,
    );
  }

  final String id;
  final String type;
  final Map<String, dynamic>? data;
  final String? readAt;
  final String? createdAt;

  /// Denormalized by Laravel API (`UserNotificationController@index`).
  final String? title;
  final String? preview;

  /// Web URL to open after marking read (conversation, reflection page, etc.).
  final String? url;

  bool get isUnread => readAt == null || readAt!.isEmpty;
}

class ReflectionPrompt {
  ReflectionPrompt({
    required this.id,
    required this.title,
    required this.body,
    this.publishedAt,
    this.myResponse,
  });

  factory ReflectionPrompt.fromJson(Map<String, dynamic> j) {
    final idRaw = j['id'];
    final id = idRaw is int ? idRaw : (idRaw is num ? idRaw.toInt() : int.parse(idRaw.toString()));
    ReflectionMyResponse? mine;
    final mr = j['my_response'];
    if (mr is Map<String, dynamic>) {
      mine = ReflectionMyResponse.fromJson(mr);
    }
    return ReflectionPrompt(
      id: id,
      title: j['title'] as String? ?? '',
      body: j['body'] as String? ?? '',
      publishedAt: j['published_at'] as String?,
      myResponse: mine,
    );
  }

  final int id;
  final String title;
  final String body;
  final String? publishedAt;
  final ReflectionMyResponse? myResponse;
}

class ReflectionMyResponse {
  ReflectionMyResponse({
    required this.body,
    this.isPublic = false,
  });

  factory ReflectionMyResponse.fromJson(Map<String, dynamic> j) {
    final ip = j['is_public'];
    return ReflectionMyResponse(
      body: j['body'] as String? ?? '',
      isPublic: ip is bool ? ip : (ip == true),
    );
  }

  final String body;
  final bool isPublic;
}
