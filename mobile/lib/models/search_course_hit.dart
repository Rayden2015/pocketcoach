class SearchCourseHit {
  const SearchCourseHit({
    required this.id,
    required this.title,
    this.summary,
    this.tenantSlug,
    this.tenantName,
    this.programTitle,
  });

  factory SearchCourseHit.fromJson(Map<String, dynamic> j) {
    final rawId = j['id'];
    final id = rawId is int ? rawId : (rawId is num ? rawId.toInt() : int.parse(rawId.toString()));
    return SearchCourseHit(
      id: id,
      title: j['title'] as String? ?? '',
      summary: j['summary'] as String?,
      tenantSlug: j['tenant_slug'] as String?,
      tenantName: j['tenant_name'] as String?,
      programTitle: j['program_title'] as String?,
    );
  }

  final int id;
  final String title;
  final String? summary;
  final String? tenantSlug;
  final String? tenantName;
  final String? programTitle;
}
