class BookableCoach {
  const BookableCoach({required this.id, required this.name, required this.email});

  final int id;
  final String name;
  final String email;

  factory BookableCoach.fromJson(Map<String, dynamic> j) {
    return BookableCoach(
      id: (j['id'] as num).toInt(),
      name: (j['name'] as String?) ?? '',
      email: (j['email'] as String?) ?? '',
    );
  }

  String get displayLabel => name.trim().isNotEmpty ? name : email;
}

class BookingSlot {
  const BookingSlot({
    required this.start,
    required this.end,
    required this.startLocal,
    required this.endLocal,
  });

  final String start;
  final String end;
  final String startLocal;
  final String endLocal;

  factory BookingSlot.fromJson(Map<String, dynamic> j) {
    return BookingSlot(
      start: j['start'] as String,
      end: j['end'] as String,
      startLocal: j['start_local'] as String,
      endLocal: j['end_local'] as String,
    );
  }

  @override
  bool operator ==(Object other) =>
      other is BookingSlot && other.start == start && other.end == end;

  @override
  int get hashCode => Object.hash(start, end);
}

class CoachBookingRow {
  const CoachBookingRow({
    required this.id,
    required this.status,
    required this.startsAt,
    required this.endsAt,
    required this.bookerDisplayName,
    this.bookerEmail,
    this.bookerMessage,
  });

  final int id;
  final String status;
  final String startsAt;
  final String endsAt;
  final String bookerDisplayName;
  final String? bookerEmail;
  final String? bookerMessage;

  factory CoachBookingRow.fromJson(Map<String, dynamic> j) {
    return CoachBookingRow(
      id: (j['id'] as num).toInt(),
      status: j['status'] as String? ?? '',
      startsAt: j['starts_at'] as String? ?? '',
      endsAt: j['ends_at'] as String? ?? '',
      bookerDisplayName: j['booker_display_name'] as String? ?? '',
      bookerEmail: j['booker_email'] as String?,
      bookerMessage: j['booker_message'] as String?,
    );
  }
}
