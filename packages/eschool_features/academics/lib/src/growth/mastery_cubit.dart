import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'mastery_repository.dart';

class MasteryState {
  const MasteryState({
    this.busy = false,
    this.hidden = false,
    this.error,
    this.configuration,
    this.roster,
    this.subjects,
    this.view,
    this.groupId,
    this.learnerId,
    this.subjectId,
  });
  final bool busy, hidden;
  final String? error;
  final MasteryConfiguration? configuration;
  final MasteryRoster? roster;
  final MasterySubjectList? subjects;
  final MasteryView? view;
  final int? groupId, learnerId, subjectId;
}

class MasteryCubit extends Cubit<MasteryState> {
  MasteryCubit(this.repository, {this.familyLearner})
    : super(const MasteryState());
  final MasteryRepository repository;
  final int? familyLearner;
  int _epoch = 0;
  int? _group, _learner, _subject;
  bool _current(int epoch) {
    if (isClosed || epoch != _epoch) return false;
    if (!repository.isActive) {
      emit(
        const MasteryState(
          error: 'Mastery is unavailable for this account or school.',
        ),
      );
      return false;
    }
    return true;
  }

  String _error(Object error) =>
      error is ApiException && [403, 404].contains(error.statusCode)
      ? 'Mastery is unavailable for this account or school.'
      : 'Mastery could not be loaded. Check your connection and retry.';
  void hide() {
    _epoch++;
    if (!isClosed) emit(const MasteryState(hidden: true));
  }

  Future<void> load() async {
    if (isClosed) return;
    final epoch = ++_epoch;
    emit(const MasteryState(busy: true));
    try {
      if (!repository.isActive) {
        throw ApiException('scope_changed', statusCode: 403);
      }
      if (familyLearner != null) {
        final choices = await repository.subjects(familyLearner!);
        if (!_current(epoch)) return;
        final selected =
            choices.subjects.where((s) => s.id == _subject).firstOrNull ??
            choices.subjects.firstOrNull;
        _subject = selected?.id;
        if (selected == null) {
          emit(MasteryState(subjects: choices, learnerId: familyLearner));
          return;
        }
        final view = await repository.family(familyLearner!, selected.id);
        if (_current(epoch)) {
          emit(
            MasteryState(
              subjects: choices,
              view: view,
              learnerId: familyLearner,
              subjectId: selected.id,
            ),
          );
        }
      } else {
        final config = await repository.configuration();
        if (!_current(epoch)) return;
        final selected =
            config.groups.where((g) => g.id == _group).firstOrNull ??
            config.groups.firstOrNull;
        _group = selected?.id;
        if (selected == null) {
          emit(MasteryState(configuration: config));
          return;
        }
        await _groupData(epoch, config, selected);
      }
    } catch (error) {
      if (!isClosed && epoch == _epoch) {
        emit(MasteryState(error: _error(error)));
      }
    }
  }

  Future<void> _groupData(
    int epoch,
    MasteryConfiguration config,
    MasteryGroup group, {
    int page = 1,
  }) async {
    final roster = await repository.roster(group.id, page: page);
    if (!_current(epoch)) return;
    final selected =
        roster.learners.where((l) => l.learnerId == _learner).firstOrNull ??
        roster.learners.firstOrNull;
    _learner = selected?.learnerId;
    if (selected == null) {
      emit(
        MasteryState(configuration: config, roster: roster, groupId: group.id),
      );
      return;
    }
    final view = await repository.staff(
      group.id,
      selected.learnerId,
      group.subjectId,
    );
    if (_current(epoch)) {
      emit(
        MasteryState(
          configuration: config,
          roster: roster,
          view: view,
          groupId: group.id,
          learnerId: selected.learnerId,
          subjectId: group.subjectId,
        ),
      );
    }
  }

  Future<void> group(int id, {int page = 1}) async {
    if (isClosed) return;
    final config = state.configuration;
    final choice = config?.groups.where((g) => g.id == id).firstOrNull;
    if (choice == null || !repository.isActive) {
      hide();
      return;
    }
    final epoch = ++_epoch;
    _group = id;
    emit(MasteryState(busy: true, configuration: config, groupId: id));
    try {
      await _groupData(epoch, config!, choice, page: page);
    } catch (error) {
      if (!isClosed && epoch == _epoch) {
        emit(MasteryState(error: _error(error)));
      }
    }
  }

  Future<void> learner(int id) async {
    if (isClosed) return;
    final old = state, config = state.configuration, roster = state.roster;
    final group = config?.groups.where((g) => g.id == old.groupId).firstOrNull;
    if (roster?.learners.any((l) => l.learnerId == id) != true ||
        group == null ||
        !repository.isActive) {
      hide();
      return;
    }
    _learner = id;
    final epoch = ++_epoch;
    emit(
      MasteryState(
        busy: true,
        configuration: config,
        roster: roster,
        groupId: group.id,
        learnerId: id,
      ),
    );
    try {
      final view = await repository.staff(group.id, id, group.subjectId);
      if (_current(epoch)) {
        emit(
          MasteryState(
            configuration: config,
            roster: roster,
            groupId: group.id,
            learnerId: id,
            subjectId: group.subjectId,
            view: view,
          ),
        );
      }
    } catch (error) {
      if (!isClosed && epoch == _epoch) {
        emit(MasteryState(error: _error(error)));
      }
    }
  }

  Future<void> subject(int id) async {
    if (isClosed) return;
    if (state.subjects?.subjects.any((s) => s.id == id) != true ||
        !repository.isActive) {
      hide();
      return;
    }
    _subject = id;
    await load();
  }

  @override
  Future<void> close() {
    _epoch++;
    return super.close();
  }
}
