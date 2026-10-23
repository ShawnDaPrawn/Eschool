//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/handle_proposal.dart';
import 'package:json_annotation/json_annotation.dart';

part 'handle_state.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class HandleState {
  /// Returns a new [HandleState] instance.
  HandleState({
    required this.handle,

    required this.proposal,

    required this.canChangeAfter,
  });

  @JsonKey(name: r'handle', required: true, includeIfNull: true)
  final String? handle;

  @JsonKey(name: r'proposal', required: true, includeIfNull: true)
  final HandleProposal? proposal;

  @JsonKey(name: r'can_change_after', required: true, includeIfNull: true)
  final DateTime? canChangeAfter;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is HandleState &&
          other.handle == handle &&
          other.proposal == proposal &&
          other.canChangeAfter == canChangeAfter;

  @override
  int get hashCode =>
      (handle == null ? 0 : handle.hashCode) +
      (proposal == null ? 0 : proposal.hashCode) +
      (canChangeAfter == null ? 0 : canChangeAfter.hashCode);

  factory HandleState.fromJson(Map<String, dynamic> json) =>
      _$HandleStateFromJson(json);

  Map<String, dynamic> toJson() => _$HandleStateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
