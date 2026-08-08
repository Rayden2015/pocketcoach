class ConversationMessage {
  ConversationMessage({
    required this.id,
    required this.body,
    this.parentId,
    this.authorName,
    this.createdAt,
  });

  factory ConversationMessage.fromJson(Map<String, dynamic> j) {
    final user = j['user'];
    String? name;
    if (user is Map<String, dynamic>) {
      name = user['name'] as String?;
    }
    final idRaw = j['id'];
    final id = idRaw is int ? idRaw : (idRaw is num ? idRaw.toInt() : int.parse(idRaw.toString()));
    final parentRaw = j['parent_id'];
    int? parentId;
    if (parentRaw is int) {
      parentId = parentRaw;
    } else if (parentRaw is num) {
      parentId = parentRaw.toInt();
    }
    return ConversationMessage(
      id: id,
      body: j['body'] as String? ?? '',
      parentId: parentId,
      authorName: name,
      createdAt: j['created_at'] as String?,
    );
  }

  final int id;
  final String body;
  final int? parentId;
  final String? authorName;
  final String? createdAt;
}
