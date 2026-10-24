/// Synthetic timetable data for client tests; never calls a network.
Map<String, dynamic> timetableLessonFixture({
  int id = 1,
  String room = 'Room 101',
}) => {
  'id': id,
  'revision': 1,
  'session_year_id': 1,
  'term_id': null,
  'teaching_group_id': 1,
  'class_section_id': null,
  'teacher_id': 31,
  'teacher_name': 'Sipho Nkosi',
  'room_id': 7,
  'room_name': room,
  'building': 'Main',
  'bell_period_id': 2,
  'period_name': 'Period 1',
  'starts_at': '08:00',
  'ends_at': '08:45',
  'title': 'Maths 7A',
  'subject_name': 'Mathematics',
  'date': '2026-02-10',
  'weekday': 2,
  'starts_on': '2026-02-10',
  'ends_on': '2026-02-10',
  'occurs_on': null,
  'week_pattern': 'all',
  'active': true,
};
Map<String, dynamic> timetableWeekFixture({
  bool manage = false,
  String startsOn = '2026-02-09',
}) => {
  'session_year_id': 1,
  'starts_on': startsOn,
  'ends_on': DateTime.parse(
    '${startsOn}T12:00:00Z',
  ).add(const Duration(days: 6)).toIso8601String().substring(0, 10),
  'can_manage': manage,
  'lessons': [timetableLessonFixture()],
};
Map<String, dynamic> timetableConfigurationFixture() => {
  'rooms': [
    {
      'id': 7,
      'name': 'Room 101',
      'building': 'Main',
      'capacity': 35,
      'type': 'classroom',
      'active': true,
      'revision': 1,
    },
  ],
  'bell_schedules': [
    {
      'id': 1,
      'session_year_id': 1,
      'name': 'Senior bells',
      'section': 'Senior',
      'revision': 1,
      'periods': [
        {
          'id': 2,
          'name': 'Period 1',
          'position': 1,
          'starts_at': '08:00',
          'ends_at': '08:45',
          'is_break': false,
        },
      ],
    },
  ],
  'teachers': [
    {'id': 31, 'name': 'Sipho Nkosi'},
  ],
  'homerooms': [
    {'id': 3, 'name': 'Grade 7 A'},
  ],
};
Map<String, dynamic> timetableGroupsFixture() => {
  'groups': [
    {
      'id': 1,
      'name': 'Maths 7A',
      'year_name': '2026',
      'subject_id': 1,
      'grade_level_id': 8,
      'session_year_id': 1,
      'class_section_id': 3,
      'active': true,
      'revision': 1,
    },
  ],
  'current_page': 1,
  'last_page': 1,
  'total': 1,
};
