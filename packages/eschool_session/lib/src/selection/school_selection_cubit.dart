import 'package:equatable/equatable.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_session/src/context/me_context.dart';
import 'package:eschool_session/src/selection/selection_store.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class SchoolSelectionState extends Equatable {
  const SchoolSelectionState({required this.schools, this.selectedId});

  /// Every school the user belongs to, from `/me/context` `schools`.
  final List<SchoolMembership> schools;

  /// The school every API call is sent for (`X-School-Id`), or `null` when
  /// none was chosen (the server then uses the user's own school).
  final int? selectedId;

  SchoolMembership? get selected {
    for (final SchoolMembership school in schools) {
      if (school.school.id == selectedId) {
        return school;
      }
    }
    return null;
  }

  /// Whether the user belongs to more than one school: only then is the
  /// school switcher shown.
  bool get canSwitch => schools.length > 1;

  @override
  List<Object?> get props => [schools, selectedId];
}

/// The school a person at several schools is working in. The choice is
/// kept in the [SelectionStore], where the API client reads it for the
/// `X-School-Id` header of every call, so it survives a restart; it is
/// cleared at sign-out.
///
/// Switching school is [select] followed by a fresh `/me/context` and a
/// return to home (see `showSchoolPicker`). A guardian's child screens are
/// sent for the child's school: picking a child at another school moves
/// the selection with [follow].
class SchoolSelectionCubit extends Cubit<SchoolSelectionState> {
  SchoolSelectionCubit({required SelectionStore store})
    : _store = store,
      super(
        SchoolSelectionState(schools: const [], selectedId: store.schoolId),
      );

  final SelectionStore _store;

  /// Lists the schools [context] names. The school chosen before stays
  /// chosen while the user still belongs to it; otherwise the context's
  /// current school is used. A context without schools (an older server, or
  /// none yet) changes nothing.
  Future<void> syncWithContext(MeContext? context) async {
    if (context == null || context.schools.isEmpty) {
      return;
    }
    final List<SchoolMembership> schools = context.schools;
    bool listed(int? id) =>
        id != null && schools.any((school) => school.school.id == id);
    int? selectedId;
    for (final int? candidate in [
      state.selectedId,
      _store.schoolId,
      context.currentSchoolId,
    ]) {
      if (listed(candidate)) {
        selectedId = candidate;
        break;
      }
    }
    if (isClosed) {
      return;
    }
    emit(SchoolSelectionState(schools: schools, selectedId: selectedId));
    final int? stored = _store.schoolId;
    if (stored != null && !listed(stored)) {
      // The user left that school: stop sending it.
      await _store.setSchoolId(schools.length > 1 ? selectedId : null);
    } else if (schools.length > 1 && selectedId != stored) {
      // Several schools: every call says which one. One school needs no
      // header, the server knows it.
      await _store.setSchoolId(selectedId);
    }
  }

  /// Works in [schoolId] from now on. Answers whether anything changed: a
  /// school the user does not belong to, or the one already chosen, is not.
  Future<bool> select(int schoolId) async {
    if (schoolId == state.selectedId ||
        !state.schools.any((school) => school.school.id == schoolId) ||
        isClosed) {
      return false;
    }
    emit(SchoolSelectionState(schools: state.schools, selectedId: schoolId));
    await _store.setSchoolId(schoolId);
    return true;
  }

  /// Follows a guardian's child to their school ([schoolId] from the
  /// child). Answers whether the school changed.
  Future<bool> follow(int schoolId) async {
    if (schoolId <= 0 || schoolId == state.selectedId) {
      return false;
    }
    if (state.schools.isNotEmpty) {
      return select(schoolId);
    }
    // No list yet (an older server sends none): send the child's school.
    if (isClosed) {
      return false;
    }
    emit(SchoolSelectionState(schools: state.schools, selectedId: schoolId));
    await _store.setSchoolId(schoolId);
    return true;
  }

  /// Forgets the schools and the choice: at sign-out.
  Future<void> clear() async {
    await _store.setSchoolId(null);
    if (!isClosed) {
      emit(const SchoolSelectionState(schools: []));
    }
  }
}
