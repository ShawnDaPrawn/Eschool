import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'timetable_repository.dart';

class TimetableState {
  const TimetableState({
    this.week,
    this.configuration,
    this.groups = const [],
    this.loading = true,
    this.busy = false,
    this.error,
    this.draft,
    this.preview,
    this.saved = false,
  });
  final AcademicTimetableWeekData? week;
  final AcademicTimetableConfigurationData? configuration;
  final List<TeachingGroup> groups;
  final bool loading, busy, saved;
  final String? error;
  final Map<String, dynamic>? draft;
  final AcademicTimetablePreviewData? preview;
  TimetableState copy({
    AcademicTimetableWeekData? week,
    AcademicTimetableConfigurationData? configuration,
    List<TeachingGroup>? groups,
    bool? loading,
    bool? busy,
    bool? saved,
    String? error,
    Map<String, dynamic>? draft,
    bool clearDraft = false,
    AcademicTimetablePreviewData? preview,
    bool clearPreview = false,
  }) => TimetableState(
    week: week ?? this.week,
    configuration: configuration ?? this.configuration,
    groups: groups ?? this.groups,
    loading: loading ?? this.loading,
    busy: busy ?? this.busy,
    saved: saved ?? this.saved,
    error: error,
    draft: clearDraft ? null : draft ?? this.draft,
    preview: clearPreview ? null : preview ?? this.preview,
  );
}

class TimetableCubit extends Cubit<TimetableState> {
  TimetableCubit(this.repository, {this.learner})
    : super(const TimetableState());
  final TimetableRepository repository;
  final int? learner;
  int _generation = 0, _draftGeneration = 0;
  String _message(Object error) => error is ApiException && error.isApiMessage
      ? error.errorMessage
      : 'Could not load the timetable. Please try again.';

  Future<void> load({String? startsOn, int? year}) async {
    final generation = ++_generation;
    _draftGeneration++;
    emit(const TimetableState());
    try {
      final week = await repository.week(
        startsOn: startsOn,
        year: year,
        learner: learner,
      );
      if (isClosed || generation != _generation) return;
      AcademicTimetableConfigurationData? configuration;
      List<TeachingGroup> groups = [];
      // Family screens never fetch staff configuration or teaching-group lists.
      if (learner == null && week.canManage) {
        configuration = await repository.configuration(week.sessionYearId);
        groups = await repository.groups(week.sessionYearId);
      }
      if (!isClosed && generation == _generation) {
        emit(
          TimetableState(
            week: week,
            configuration: configuration,
            groups: groups,
            loading: false,
          ),
        );
      }
    } catch (error) {
      if (!isClosed && generation == _generation) {
        emit(TimetableState(loading: false, error: _message(error)));
      }
    }
  }

  void edit([AcademicTimetableLesson? lesson]) {
    if (state.loading ||
        state.busy ||
        state.week?.canManage != true ||
        state.configuration == null) {
      return;
    }
    _draftGeneration++;
    final firstDay = state.week!.startsOn;
    final draft =
        lesson?.toJson() ??
        <String, dynamic>{
          'session_year_id': state.week!.sessionYearId,
          'teaching_group_id': null,
          'class_section_id': null,
          'teacher_id': null,
          'room_id': null,
          'bell_period_id': null,
          'term_id': null,
          'starts_on': firstDay,
          'ends_on': state.week!.endsOn,
          'occurs_on': null,
          'weekday': DateTime.parse(firstDay).weekday,
          'week_pattern': 'all',
          'active': true,
        };
    // Output labels and actual-occurrence dates are read fields, never write fields.
    final allowed = {
      'id',
      'revision',
      'session_year_id',
      'term_id',
      'teaching_group_id',
      'class_section_id',
      'teacher_id',
      'room_id',
      'bell_period_id',
      'starts_on',
      'ends_on',
      'occurs_on',
      'weekday',
      'week_pattern',
      'active',
    };
    emit(
      state.copy(
        draft: Map.unmodifiable(
          Map<String, dynamic>.fromEntries(
            draft.entries.where((entry) => allowed.contains(entry.key)),
          ),
        ),
        clearPreview: true,
        saved: false,
      ),
    );
  }

  void change(Map<String, dynamic> values) {
    if (state.draft == null || state.busy) return;
    _draftGeneration++;
    emit(
      state.copy(
        draft: Map.unmodifiable({...state.draft!, ...values}),
        clearPreview: true,
      ),
    );
  }

  void cancelEdit() {
    if (isClosed || state.busy) return;
    _draftGeneration++;
    emit(state.copy(clearDraft: true, clearPreview: true));
  }

  Future<void> preview() async {
    if (state.draft == null || state.busy || state.week?.canManage != true) {
      return;
    }
    final generation = _generation, draft = ++_draftGeneration;
    emit(state.copy(busy: true, clearPreview: true));
    try {
      final input = AcademicLessonWrite.fromJson(state.draft!);
      final result = await repository.preview([input]);
      if (!isClosed && generation == _generation && draft == _draftGeneration) {
        emit(state.copy(busy: false, preview: result));
      }
    } catch (error) {
      if (!isClosed && generation == _generation && draft == _draftGeneration) {
        if (error is ApiException && [401, 403].contains(error.statusCode)) {
          emit(TimetableState(loading: false, error: _message(error)));
        } else {
          emit(
            state.copy(
              busy: false,
              clearPreview: true,
              error: error is ApiException
                  ? _message(error)
                  : 'Choose a group or homeroom, teacher, room, period and valid dates first.',
            ),
          );
        }
      }
    }
  }

  Future<bool> save() async {
    if (state.preview?.canSave != true || state.busy || state.draft == null) {
      return false;
    }
    final generation = _generation;
    final week = state.week!;
    final preview = state.preview!;
    emit(state.copy(busy: true));
    try {
      await repository.commit(
        AcademicTimetableCommitWrite(
          rows: preview.rows,
          fingerprint: preview.fingerprint,
        ),
      );
      if (isClosed || generation != _generation) return false;
      await load(startsOn: week.startsOn, year: week.sessionYearId);
      if (!isClosed && !state.loading && state.week != null) {
        emit(state.copy(saved: true));
      }
      return true;
    } catch (error) {
      if (!isClosed && generation == _generation) {
        if (error is ApiException && [401, 403].contains(error.statusCode)) {
          emit(TimetableState(loading: false, error: _message(error)));
        } else {
          emit(
            state.copy(busy: false, clearPreview: true, error: _message(error)),
          );
        }
      }
      return false;
    }
  }

  @override
  Future<void> close() {
    _generation++;
    _draftGeneration++;
    if (!isClosed) {
      emit(
        const TimetableState(
          loading: false,
          error: 'School context changed. Open the timetable again.',
        ),
      );
    }
    return super.close();
  }
}
