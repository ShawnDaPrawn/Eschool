/// A bounded reference to changed data. Content always comes from its protected reader.
class LiveInvalidation {
  const LiveInvalidation._({
    required this.type,
    required this.schoolId,
    required this.revision,
    this.sessionYearId,
    this.houseId,
    this.groupId,
    this.learnerId,
    this.sourceId,
    this.recognitionKind,
    this.consultationId,
    this.onlineCount,
  });
  final String type;
  final int schoolId, revision;
  final int? sessionYearId, houseId, groupId, learnerId, sourceId;
  final String? recognitionKind;
  final int? consultationId, onlineCount;

  factory LiveInvalidation.fromJson(
    Map<String, dynamic> json, {
    required int expectedSchoolId,
  }) {
    final type = json['type'];
    final fields = switch (type) {
      'house_points.changed' => ['session_year_id', 'house_id'],
      'recognition.changed' => [
        'session_year_id',
        'learner_id',
        'source_id',
        'kind',
      ],
      'activity.group_changed' => ['group_id'],
      'group.presence' => ['group_id', 'online_count'],
      'consultations.counts_changed' => ['consultation_id'],
      _ => throw const FormatException('Unsupported live event.'),
    };
    final allowed = {'type', 'school_id', 'revision', ...fields};
    if (json.length != allowed.length ||
        !json.keys.every(allowed.contains) ||
        json['school_id'] != expectedSchoolId ||
        expectedSchoolId <= 0) {
      throw const FormatException('Invalid live scope.');
    }
    int positive(String key) {
      final value = json[key];
      if (value is! int || value <= 0 || value > 9007199254740991) {
        throw const FormatException('Invalid live reference.');
      }
      return value;
    }

    int? optional(String key) => fields.contains(key) ? positive(key) : null;
    final kind = json['kind'];
    if (fields.contains('kind') && !const ['point', 'award'].contains(kind)) {
      throw const FormatException('Invalid recognition kind.');
    }
    final count = json['online_count'];
    if (fields.contains('online_count') &&
        (count is! int || count < 0 || count > 1000000)) {
      throw const FormatException('Invalid presence count.');
    }
    return LiveInvalidation._(
      type: type as String,
      schoolId: positive('school_id'),
      revision: positive('revision'),
      sessionYearId: optional('session_year_id'),
      houseId: optional('house_id'),
      groupId: optional('group_id'),
      learnerId: optional('learner_id'),
      sourceId: optional('source_id'),
      recognitionKind: kind as String?,
      consultationId: optional('consultation_id'),
      onlineCount: count as int?,
    );
  }

  /// Revisions are per source, not globally interchangeable (each new point is revision1).
  String get sourceKey => [
    type,
    schoolId,
    sessionYearId,
    houseId,
    groupId,
    learnerId,
    sourceId,
    consultationId,
  ].join(':');
}
