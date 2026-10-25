//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_revision_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferRevisionInput {
  /// Returns a new [TransferRevisionInput] instance.
  TransferRevisionInput({required this.operationId, required this.revision});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferRevisionInput &&
          other.operationId == operationId &&
          other.revision == revision;

  @override
  int get hashCode => operationId.hashCode + revision.hashCode;

  factory TransferRevisionInput.fromJson(Map<String, dynamic> json) =>
      _$TransferRevisionInputFromJson(json);

  Map<String, dynamic> toJson() => _$TransferRevisionInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
