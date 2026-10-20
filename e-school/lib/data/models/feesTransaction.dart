import 'package:eschool/data/models/sessionYear.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/labelKeys.dart';

class FeesTransaction {

  FeesTransaction({
    required this.id,
    required this.studentId,
    required this.classId,
    required this.parentId,
    required this.mode,
    required this.typeOfFee,
    required this.paymentGateway,
    required this.studentFirstName,
    required this.studentLastName,
    required this.paymentStatus, required this.totalAmount, required this.sessionYear, this.orderId,
    this.paymentId,
    this.paymentSignature,
    this.chequeNo,
    this.date,
  });

  factory FeesTransaction.fromJson(Map<String, dynamic> map) {
    return FeesTransaction(
      id: map['id'] as int,
      studentId: map['student_id'] as int,
      studentFirstName: map['student']['user']['first_name'] as String,
      studentLastName: map['student']['user']['last_name'] as String,
      date: DateTime.tryParse(map['date'].toString()),
      classId: map['class_id'] as int,
      parentId: map['parent_id'] as int,
      chequeNo: map['cheque_no'],
      mode: map['mode'] as int,
      typeOfFee: map['type_of_fee'] as int,
      paymentGateway: map['payment_gateway'] ?? 0,
      orderId: map['order_id'],
      paymentId: map['payment_id'],
      paymentSignature: map['payment_signature'],
      paymentStatus: map['payment_status'] as int,
      totalAmount: double.parse(map['total_amount'].toString()),
      sessionYear: SessionYear.fromJson(
        map['session_year'] as Map<String, dynamic>,
      ),
    );
  }
  int id;
  int studentId;
  String studentFirstName;
  String studentLastName;
  int classId;
  int parentId;
  int mode;
  int typeOfFee;
  int paymentGateway;
  String? chequeNo;
  String? orderId;
  String? paymentId;
  String? paymentSignature;
  DateTime? date;
  int paymentStatus;
  double totalAmount;
  SessionYear sessionYear;

  String get modeImagePath {
    switch (mode) {
      case 1:
        return Assets.chequePaymentIcon;
      case 2:
        return Assets.onlinePaymentIcon;
      default:
        return Assets.cashPaymentIcon;
    }
  }

  String get fullName => '$studentFirstName $studentLastName';

  String get feeTypeKey {
    switch (typeOfFee) {
      case 1:
        return instalmentKey;
      case 2:
        return optionalTitleKey;
      default:
        return compulsoryTitleKey;
    }
  }

  String get modeKey {
    switch (mode) {
      case 1:
        return chequeKey;
      case 2:
        return onlineKey;
      default:
        return cashKey;
    }
  }

  String get paymentGatewayString {
    switch (paymentGateway) {
      case 2:
        return 'Stripe';
      case 3:
        return 'PayStack';
      default:
        return 'Razorpay';
    }
  }

  String get paymentStatusKey {
    switch (paymentStatus) {
      case 1:
        return successKey;
      case 2:
        return pendingKey;
      default:
        return failedKey;
    }
  }
}
