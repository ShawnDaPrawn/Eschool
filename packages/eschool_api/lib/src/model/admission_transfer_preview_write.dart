//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_transfer_preview_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionTransferPreviewWrite {
  /// Returns a new [AdmissionTransferPreviewWrite] instance.
  AdmissionTransferPreviewWrite({required this.receiveCode});

  @JsonKey(name: r'receive_code', required: true, includeIfNull: false)
  final String receiveCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionTransferPreviewWrite &&
          other.receiveCode == receiveCode;

  @override
  int get hashCode => receiveCode.hashCode;

  factory AdmissionTransferPreviewWrite.fromJson(Map<String, dynamic> json) =>
      _$AdmissionTransferPreviewWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionTransferPreviewWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
