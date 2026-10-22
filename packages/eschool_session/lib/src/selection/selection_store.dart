import 'package:eschool_session/src/auth/session_repository.dart';
import 'package:hive/hive.dart';

/// The child and the school picked in the switchers, kept on the device so
/// the app opens where the user left it. Cleared at sign-out.
abstract interface class SelectionStore {
  /// The learner id of the child a guardian picked, or `null`.
  int? get childId;
  Future<void> setChildId(int? value);

  /// The school picked, sent with every API call as `X-School-Id`; `null`
  /// before a school is chosen (the server then uses the user's own).
  int? get schoolId;
  Future<void> setSchoolId(int? value);

  Future<void> clear();
}

/// [SelectionStore] in the `authBox`, next to the session it belongs to.
class HiveSelectionStore implements SelectionStore {
  HiveSelectionStore({Box<dynamic>? box}) : _box = box;

  final Box<dynamic>? _box;

  Box<dynamic> get _authBox => _box ?? Hive.box(SessionBoxKeys.authBox);

  int? _read(String key) {
    final Object? value = _authBox.get(key);
    return value is int && value > 0 ? value : null;
  }

  Future<void> _write(String key, int? value) => value == null || value <= 0
      ? _authBox.delete(key)
      : _authBox.put(key, value);

  @override
  int? get childId => _read(SessionBoxKeys.selectedChildId);

  @override
  Future<void> setChildId(int? value) =>
      _write(SessionBoxKeys.selectedChildId, value);

  @override
  int? get schoolId => _read(SessionBoxKeys.selectedSchoolId);

  @override
  Future<void> setSchoolId(int? value) =>
      _write(SessionBoxKeys.selectedSchoolId, value);

  @override
  Future<void> clear() async {
    await _authBox.delete(SessionBoxKeys.selectedChildId);
    await _authBox.delete(SessionBoxKeys.selectedSchoolId);
  }
}

/// A [SelectionStore] kept only in memory, for tests.
class InMemorySelectionStore implements SelectionStore {
  InMemorySelectionStore({this.childId, this.schoolId});

  @override
  int? childId;

  @override
  int? schoolId;

  @override
  Future<void> setChildId(int? value) async => childId = value;

  @override
  Future<void> setSchoolId(int? value) async => schoolId = value;

  @override
  Future<void> clear() async {
    childId = null;
    schoolId = null;
  }
}
