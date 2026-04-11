class CatalogProgram {
  CatalogProgram({
    required this.id,
    required this.title,
    required this.slug,
    this.summary,
    required this.courses,
  });

  factory CatalogProgram.fromJson(Map<String, dynamic> j) {
    final idRaw = j['id'];
    final id = idRaw is int ? idRaw : (idRaw is num ? idRaw.toInt() : int.parse(idRaw.toString()));
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
      id: id,
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
    this.isEnrolled = false,
    this.freeProductId,
  });

  factory CatalogCourse.fromJson(Map<String, dynamic> j) {
    final fp = j['free_product_id'];
    int? freeId;
    if (fp is int) {
      freeId = fp;
    } else if (fp is num) {
      freeId = fp.toInt();
    }
    final idRaw = j['id'];
    final cid = idRaw is int ? idRaw : (idRaw is num ? idRaw.toInt() : int.parse(idRaw.toString()));
    return CatalogCourse(
      id: cid,
      title: j['title'] as String,
      slug: j['slug'] as String,
      summary: j['summary'] as String?,
      isEnrolled: j['is_enrolled'] as bool? ?? false,
      freeProductId: freeId,
    );
  }

  final int id;
  final String title;
  final String slug;
  final String? summary;
  final bool isEnrolled;
  final int? freeProductId;

  bool get canEnrollFree => !isEnrolled && freeProductId != null;
}
