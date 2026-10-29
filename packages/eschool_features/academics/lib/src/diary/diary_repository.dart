import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

/// A note or a to-do as the learner writes it. Only the learner reads it.
class DiaryDraft {
  const DiaryDraft({
    required this.todo,
    required this.title,
    this.body,
    this.date,
    this.remindAt,
    this.done = false,
  });

  /// A to-do can be ticked off; a note cannot.
  final bool todo;
  final String title;
  final String? body;

  /// `YYYY-MM-DD`, or null for an undated to-do.
  final String? date;

  /// School wall time, `YYYY-MM-DD HH:mm`.
  final String? remindAt;
  final bool done;

  static const maxTitle = 200, maxBody = 10000;

  /// The first reason the server would refuse this draft, as a label key.
  String? get problem {
    final title = this.title.trim();
    if (title.isEmpty) return 'Write a title.';
    if (title.length > maxTitle) return 'Keep the title to 200 characters.';
    if ((body ?? '').length > maxBody) {
      return 'Keep the note to 10,000 characters.';
    }
    if (!todo && date == null) return 'Choose a date for the note.';
    if (date != null && !diaryDatePattern.hasMatch(date!)) {
      return 'Choose a valid date.';
    }
    if (remindAt != null && !diaryTimePattern.hasMatch(remindAt!)) {
      return 'Choose a valid reminder time.';
    }
    return null;
  }

  Map<String, dynamic> fields() {
    final body = this.body?.trim();
    return {
      'title': title.trim(),
      'body': body == null || body.isEmpty ? null : this.body,
      'date': date,
      'remind_at': remindAt,
      'done': todo && done,
    };
  }
}

final diaryDatePattern = RegExp(r'^\d{4}-\d{2}-\d{2}$');
final diaryTimePattern = RegExp(r'^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$');

/// The learner's own diary. Nothing here is visible to parents or staff.
abstract interface class DiaryRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;

  /// Up to 62 days, `from` and `to` as `YYYY-MM-DD`.
  Future<DiaryRange> range(String from, String to);

  /// [operationId] makes a retry return the entry the first try created.
  Future<DiaryEntry> create(String operationId, DiaryDraft draft);
  Future<DiaryEntry> update(int id, int revision, DiaryDraft draft);
  Future<void> delete(int id);
  Future<DiaryItemState> setItemState(
    DiarySchoolItem item, {
    required bool done,
    String? remindAt,
    String? note,
  });
}

/// Each repository belongs to one account, school and session epoch.
class ApiDiaryRepository implements DiaryRepository {
  ApiDiaryRepository(
    this.client, {
    required this.schoolId,
    required this.readerId,
    required this.contextActive,
  }) : _token = client.authToken();

  final ApiClient client;
  @override
  final int schoolId, readerId;
  final bool Function() contextActive;
  final String _token;

  @override
  bool get isActive =>
      schoolId > 0 &&
      readerId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);

  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Reopen your diary to check your access.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    Map<String, dynamic>? query,
    Map<String, dynamic>? body,
  }) async {
    _guard();
    final reply = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}diary$path',
      schoolId: schoolId,
      accountId: readerId,
      queryParameters: query,
      data: body,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    if (reply?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(reply!['data'] as Map);
  }

  void _draft(DiaryDraft draft) {
    final problem = draft.problem;
    if (problem != null) throw ArgumentError(problem);
  }

  @override
  Future<DiaryRange> range(String from, String to) async {
    if (!diaryDatePattern.hasMatch(from) || !diaryDatePattern.hasMatch(to)) {
      throw ArgumentError('Choose valid dates.');
    }
    final result = DiaryRange.fromJson(
      await _send('GET', '', query: {'from': from, 'to': to}),
    );
    if (result.schoolId != schoolId || result.readerId != readerId) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
    }
    return result;
  }

  @override
  Future<DiaryEntry> create(String operationId, DiaryDraft draft) async {
    _draft(draft);
    return DiaryEntry.fromJson(
      await _send(
        'POST',
        '/entries',
        body: {
          'operation_id': operationId,
          'kind': draft.todo ? 'todo' : 'note',
          ...draft.fields(),
        },
      ),
    );
  }

  @override
  Future<DiaryEntry> update(int id, int revision, DiaryDraft draft) async {
    if (id < 1 || revision < 1) throw ArgumentError('Choose an entry.');
    _draft(draft);
    final result = DiaryEntry.fromJson(
      await _send(
        'PUT',
        '/entries/$id',
        body: {'revision': revision, ...draft.fields()},
      ),
    );
    if (result.id != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }

  @override
  Future<void> delete(int id) async {
    if (id < 1) throw ArgumentError('Choose an entry.');
    final result = DiaryDeleted.fromJson(await _send('DELETE', '/entries/$id'));
    if (result.id != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
  }

  @override
  Future<DiaryItemState> setItemState(
    DiarySchoolItem item, {
    required bool done,
    String? remindAt,
    String? note,
  }) async {
    final source = item.source_;
    if (source == DiaryItemSource.unknownDefaultOpenApi || item.id < 1) {
      throw ArgumentError('Choose a school item.');
    }
    if (remindAt != null && !diaryTimePattern.hasMatch(remindAt)) {
      throw ArgumentError('Choose a valid reminder time.');
    }
    if ((note ?? '').length > 2000) {
      throw ArgumentError('Keep the note to 2,000 characters.');
    }
    final trimmed = note?.trim();
    final result = DiaryItemState.fromJson(
      await _send(
        'PUT',
        '/items/${source.value}/${item.id}/state',
        body: {
          'done': done,
          'remind_at': remindAt,
          'note': trimmed == null || trimmed.isEmpty ? null : note,
        },
      ),
    );
    if (result.source_ != source || result.id != item.id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }
}
