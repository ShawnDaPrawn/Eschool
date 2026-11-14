//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_contribution_state.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryContributionState {
  /// Returns a new [SharedLibraryContributionState] instance.
  SharedLibraryContributionState({
    required this.id,

    required this.state,

    required this.practiceItemId,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SharedLibraryContributionStateStateEnum.unknownDefaultOpenApi,
  )
  final SharedLibraryContributionStateStateEnum state;

  @JsonKey(name: r'practice_item_id', required: true, includeIfNull: false)
  final int practiceItemId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryContributionState &&
          other.id == id &&
          other.state == state &&
          other.practiceItemId == practiceItemId;

  @override
  int get hashCode => id.hashCode + state.hashCode + practiceItemId.hashCode;

  factory SharedLibraryContributionState.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryContributionStateFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryContributionStateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SharedLibraryContributionStateStateEnum {
  @JsonValue(r'awaiting_approval')
  awaitingApproval(r'awaiting_approval'),
  @JsonValue(r'shared')
  shared(r'shared'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SharedLibraryContributionStateStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
