import 'package:eschool/app/routes.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

/// Retain the old route for saved links without restoring the former fee lock.
class StudentFeePaymentDueScreen extends StatefulWidget {
  const StudentFeePaymentDueScreen({super.key});

  static Route route(RouteSettings routeSettings) =>
      AdaptivePageRoute(builder: (_) => const StudentFeePaymentDueScreen());

  @override
  State<StudentFeePaymentDueScreen> createState() =>
      _StudentFeePaymentDueScreenState();
}

class _StudentFeePaymentDueScreenState
    extends State<StudentFeePaymentDueScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        Navigator.of(context).pushReplacementNamed(Routes.home);
      }
    });
  }

  @override
  Widget build(BuildContext context) =>
      const Scaffold(body: Center(child: CircularProgressIndicator()));
}
