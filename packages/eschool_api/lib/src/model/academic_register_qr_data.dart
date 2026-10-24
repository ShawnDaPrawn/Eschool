//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_qr_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterQrData {
  /// Returns a new [AcademicRegisterQrData] instance.
  AcademicRegisterQrData({required this.qrCode});

  @JsonKey(name: r'qr_code', required: true, includeIfNull: false)
  final String qrCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterQrData && other.qrCode == qrCode;

  @override
  int get hashCode => qrCode.hashCode;

  factory AcademicRegisterQrData.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterQrDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterQrDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
