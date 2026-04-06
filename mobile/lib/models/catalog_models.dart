class CatalogProgram {
  CatalogProgram({
    required this.id,
    required this.title,
    required this.slug,
    this.summary,
    required this.courses,
  });

  factory CatalogProgram.fromJson(Map<String, dynamic> j) {
    final coursesRaw = j['courses'];
    final courses = <CatalogCourse>[];
    if (coursesRaw is List) {
      for (final c in coursesRaw) {
        if (c is Map<String, dynamic>) {
          courses.add(CatalogCourse.fromJson(c));
        }
      }
    }
    return CatalogProgram(
      id: j['id'] as int,
      title: j['title'] as String,
      slug: j['slug'] as String,
      summary: j['summary'] as String?,
      courses: courses,
    );
  }

  final int id;
  final String title;
  final String slug;
  final String? summary;
  final List<CatalogCourse> courses;
}

class CatalogCourse {
  CatalogCourse({
    required this.id,
    required this.title,
    required this.slug,
    this.summary,
  });

  factory CatalogCourse.fromJson(Map<String, dynamic> j) {
    return CatalogCourse(
      id: j['id'] as int,
      title: j['title'] as String,
      slug: j['slug'] as String,
      summary: j['summary'] as String?,
    );
  }

  final int id;
  final String title;
  final String slug;
  final String? summary;
}
