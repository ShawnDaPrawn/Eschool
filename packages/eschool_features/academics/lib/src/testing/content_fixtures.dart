import 'dart:convert';
import 'package:crypto/crypto.dart';

/// Synthetic learning material shared by package tests and both app smoke flows.
Map<String, dynamic> contentSelectionFixture() => {
  'subject_id': 4,
  'grade_level_id': 8,
  'session_year_id': 1,
  'title': 'Fractions',
  'summary': 'Equivalent fractions',
  'group_ids': [7],
  'topic_ids': [3],
};
Map<String, dynamic> contentConfigurationFixture({bool leader = false}) => {
  'session_year_id': 1,
  'school_years': [
    {'id': 1, 'name': '2026'},
  ],
  'groups': [
    {
      'id': 7,
      'name': 'Grade 7 Mathematics',
      'subject_id': 4,
      'subject_name': 'Mathematics',
      'grade_level_id': 8,
      'grade_name': 'Grade 7',
    },
  ],
  'topics': [
    {
      'id': 3,
      'name': 'Equivalent fractions',
      'subject_id': 4,
      'grade_level_id': 8,
      'curriculum_version': 1,
    },
  ],
  'settings': {
    'transcription_enabled': true,
    'daily_transcription_seconds': 3600,
    'revision': 1,
  },
  'can_manage': leader,
  'maximum_source_bytes': 25 * 1024 * 1024,
};
Map<String, dynamic> contentVersionFixture({
  String state = 'draft',
  int revision = 2,
}) => {
  'id': 11,
  'content_item_id': 10,
  'number': 1,
  'state': state,
  'selection': contentSelectionFixture(),
  'markdown': '# Fractions\n\nOne half equals two quarters.',
  'revision': revision,
  'failure_code': null,
  'duration_seconds': null,
  'language': null,
  'created_at': '2026-10-24T08:00:00Z',
  'published_at': state == 'published' ? '2026-10-24T09:00:00Z' : null,
  'source': {
    'name': 'lesson.md',
    'extension': 'md',
    'mime': 'text/plain',
    'bytes': 100,
    'scan_state': 'clean',
    'scanned_at': '2026-10-24T08:00:00Z',
  },
};
Map<String, dynamic> contentItemFixture({
  bool writable = true,
  int revision = 2,
  String state = 'draft',
}) => {
  'id': 10,
  'can_write': writable,
  'active': true,
  'revision': revision,
  'author_id': 12,
  'draft': state == 'published'
      ? null
      : contentVersionFixture(state: state, revision: revision),
  'published': state == 'published'
      ? contentVersionFixture(state: state, revision: revision)
      : null,
  'page': 1,
  'last_page': 1,
  'versions': [
    {
      'id': 11,
      'number': 1,
      'state': state,
      'title': 'Fractions',
      'published_at': null,
    },
  ],
};
Map<String, dynamic> contentItemsFixture() => {
  'session_year_id': 1,
  'page': 1,
  'last_page': 1,
  'items': [
    {
      'id': 10,
      'title': 'Fractions',
      'active': true,
      'draft_version_id': 11,
      'published_version_id': null,
      'draft_state': 'draft',
      'revision': 2,
      'updated_at': '2026-10-24T08:00:00Z',
    },
  ],
};
Map<String, dynamic> contentPreviewFixture() => {
  'id': 11,
  'content_item_id': 10,
  'item_revision': 2,
  'version_revision': 2,
  'selection': contentSelectionFixture(),
  'markdown': contentVersionFixture()['markdown'],
  'fingerprint': 'a' * 64,
  'learner_count': 2,
  'review_required': true,
  'requires_reason': false,
};
Map<String, dynamic> contentLibraryFixture({
  int learner = 11,
  int page = 1,
  int lastPage = 1,
}) => {
  'learner_id': learner,
  'page': page,
  'last_page': lastPage,
  'items': [
    {
      'id': 10,
      'version_id': 11,
      'title': 'Fractions',
      'summary': 'Equivalent fractions',
      'subject_id': 4,
      'subject_name': 'Mathematics',
      'published_at': '2026-10-24T09:00:00Z',
      'read_at': null,
    },
  ],
};
Map<String, dynamic> contentReaderFixture({
  int learner = 11,
  int school = 1,
  int account = 12,
}) {
  const markdown = '# Fractions\n\nOne half equals two quarters.';
  return {
    'id': 11,
    'content_item_id': 10,
    'learner_id': learner,
    'school_id': school,
    'reader_id': account,
    'title': 'Fractions',
    'summary': 'Equivalent fractions',
    'markdown': markdown,
    'body_sha256': sha256.convert(utf8.encode(markdown)).toString(),
    'published_at': '2026-10-24T09:00:00Z',
    'topics': [
      {'id': 3, 'name': 'Equivalent fractions'},
    ],
    'server_time': '2026-10-24T09:00:00Z',
    'offline_until': '2026-10-25T09:00:00Z',
  };
}
