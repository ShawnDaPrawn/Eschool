import 'package:equatable/equatable.dart';
import 'package:eschool_session/src/context/me_context.dart';
import 'package:eschool_session/src/selection/selection_store.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ChildSelectionState extends Equatable {
  const ChildSelectionState({required this.children, this.selectedId});

  /// The guardian's children, in the order the switcher lists them. Empty
  /// for everyone else.
  final List<MeChild> children;

  /// The learner id of the child every child screen shows.
  final int? selectedId;

  MeChild? get selected {
    for (final MeChild child in children) {
      if (child.id == selectedId) {
        return child;
      }
    }
    return null;
  }

  /// Whether there is anyone to switch to: the switcher shows from two
  /// children up.
  bool get canSwitch => children.length > 1;

  @override
  List<Object?> get props => [children, selectedId];
}

/// The child a guardian is looking at. Every child screen reads it instead
/// of being handed a child id, so changing child once (in the switcher)
/// changes every open screen. The choice is kept in the [SelectionStore],
/// restored when the app starts and cleared at sign-out.
///
/// The children come from `/me/context` `children` when the server sends
/// them, otherwise from what the v1 parent sign-in answered; the app calls
/// [setChildren] with whichever it has.
class ChildSelectionCubit extends Cubit<ChildSelectionState> {
  ChildSelectionCubit({required SelectionStore store})
    : _store = store,
      super(ChildSelectionState(children: const [], selectedId: store.childId));

  final SelectionStore _store;

  MeChild? get selected => state.selected;

  /// Lists [children]. The child picked before stays picked if they are
  /// still there; otherwise the one kept on the device; otherwise the first.
  Future<void> setChildren(List<MeChild> children) async {
    final Map<int, MeChild> byId = {
      for (final MeChild child in children)
        if (child.id > 0) child.id: child,
    };
    final List<MeChild> unique = byId.values.toList();
    int? selectedId;
    for (final int? candidate in [state.selectedId, _store.childId]) {
      if (candidate != null && byId.containsKey(candidate)) {
        selectedId = candidate;
        break;
      }
    }
    selectedId ??= unique.isEmpty ? null : unique.first.id;
    if (isClosed) {
      return;
    }
    emit(ChildSelectionState(children: unique, selectedId: selectedId));
    if (selectedId != null && selectedId != _store.childId) {
      await _store.setChildId(selectedId);
    }
  }

  /// Shows [childId] on every child screen. Answers the child, or `null`
  /// when they are not one of this guardian's children (nothing changes).
  Future<MeChild?> select(int childId) async {
    MeChild? chosen;
    for (final MeChild child in state.children) {
      if (child.id == childId) {
        chosen = child;
      }
    }
    if (chosen == null || isClosed) {
      return null;
    }
    emit(ChildSelectionState(children: state.children, selectedId: childId));
    await _store.setChildId(childId);
    return chosen;
  }

  /// Forgets the children and the choice: at sign-out.
  Future<void> clear() {
    final cleared = _store.setChildId(null);
    if (!isClosed) {
      emit(const ChildSelectionState(children: []));
    }
    return cleared;
  }
}
