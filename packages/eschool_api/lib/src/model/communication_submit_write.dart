//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_submit_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationSubmitWrite {
  /// Returns a new [CommunicationSubmitWrite] instance.
  CommunicationSubmitWrite({
    required this.revision,

    required this.operationId,

    required this.fingerprint,
  });

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationSubmitWrite &&
          other.revision == revision &&
          other.operationId == operationId &&
          other.fingerprint == fingerprint;

  @override
  int get hashCode =>
      revision.hashCode + operationId.hashCode + fingerprint.hashCode;

  factory CommunicationSubmitWrite.fromJson(Map<String, dynamic> json) =>
      _$CommunicationSubmitWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationSubmitWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
